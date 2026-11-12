<?php

namespace Modules\Tenant\Modules\Training\Repositories;

use Exception;
use Illuminate\Http\Request;
use Modules\Shared\Helpers\SessionManager;
use Modules\Tenant\Modules\Management\Helpers\PeriodHelper;
use Modules\Tenant\Modules\Training\Helpers\TrainingCommentHelper;
use Modules\Tenant\Modules\Training\Helpers\TrainingHelper;
use Modules\Tenant\Modules\Training\Models\Training;
use Modules\Tenant\Modules\Training\Models\TrainingComment;
use Modules\Tenant\Modules\Training\Models\TrainingContent;
use Modules\Tenant\Modules\Training\Models\TrainingPublication;
use Modules\Tenant\Modules\User\Models\User;

class TrainingCommentRepository
{
    public static function listByModel($model)
    {
        $comments = $model->comments()
            ->select([
                'training_comment.id',
                'user.id as user_id',
                'person.names as person',
                'user.avatar as photo',
                'training_comment.created_at as date',
                'training_comment.value',
            ])
            ->join('person', 'training_comment.person_id', 'person.id')
            ->join('user', 'person.id', 'user.person_id')
            ->orderBy('training_comment.created_at', 'desc')
            ->get();

        return $comments;
    }

    public static function get(int $id)
    {
        $comment = TrainingComment::select([
            'training_comment.id',
            'user.id as user_id',
            'person.names as person',
            'user.avatar as photo',
            'training_comment.created_at as date',
            'training_comment.value',
        ])
            ->join('person', 'training_comment.person_id', '=', 'person.id')
            ->join('user', 'person.id', 'user.person_id')
            ->where('training_comment.id', $id)
            ->first();

        return $comment;
    }

    public static function create(Request $request)
    {
        $session = SessionManager::get();

        $user = User::find($session->id);

        TrainingCommentHelper::validateRequest($request);

        $models = [
            "training_content" => TrainingContent::class,
            "training_publication" => TrainingPublication::class,
        ];

        $morph = $models[$request->model]::find($request->model_id);

        $training_id = $morph->contentGroup?->training_id ?? $morph->training_id;
        TrainingHelper::validateAccess($user->person_id, $training_id);
        PeriodHelper::validateByTraining($training_id);

        $training = Training::findOrFail($training_id);

        TrainingHelper::checkTrainingStatus($user, $training, true);

        $comment = $morph->comments()->create([
            'person_id' => $user->person_id,
            'value' => $request->comment,
        ]);

        $result = self::get($comment->id);

        return $result;
    }

    public static function update(int $id, Request $request)
    {
        $session = SessionManager::get();

        $user = User::find($session->id);

        $comment = TrainingComment::byKey('id', $id);

        TrainingCommentHelper::validateRequest($request, $id);

        if ($comment->person_id !== $user->person_id) {
            throw new Exception('No tiene permisos para editar este comentario');
        }

        $morph = $comment->commentable;
        $training_id = $morph->contentGroup?->training_id ?? $morph->training_id;
        PeriodHelper::validateByTraining($training_id);

        $training = Training::findOrFail($training_id);

        TrainingHelper::checkTrainingStatus($user, $training, true);

        $comment->update([
            'value' => $request->comment,
        ]);

        $result = self::get($comment->id);

        return $result;
    }

    public static function delete(int $id)
    {
        $session = SessionManager::get();

        $user = User::find($session->id);

        $comment = TrainingComment::byKey('id', $id);

        if ($comment->person_id !== $user->person_id) {
            throw new Exception('No tiene permisos para eliminar este comentario');
        }

        $morph = $comment->commentable;
        $training_id = $morph->contentGroup?->training_id ?? $morph->training_id;
        PeriodHelper::validateByTraining($training_id);

        $training = Training::findOrFail($training_id);

        TrainingHelper::checkTrainingStatus($user, $training, true);

        $comment->delete();

        return 'Comentario eliminado correctamente';
    }
}
