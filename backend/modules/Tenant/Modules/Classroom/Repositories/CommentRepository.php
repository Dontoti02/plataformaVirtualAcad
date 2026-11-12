<?php

namespace Modules\Tenant\Modules\Classroom\Repositories;

use Exception;
use Illuminate\Http\Request;
use Modules\Shared\Helpers\SessionManager;
use Modules\Tenant\Modules\Classroom\Helpers\ClassroomHelper;
use Modules\Tenant\Modules\Classroom\Helpers\CommentHelper;
use Modules\Tenant\Modules\Classroom\Models\Comment;
use Modules\Tenant\Modules\Classroom\Models\Content;
use Modules\Tenant\Modules\Classroom\Models\Publication;
use Modules\Tenant\Modules\Management\Helpers\PeriodHelper;
use Modules\Tenant\Modules\Training\Models\TrainingContent;
use Modules\Tenant\Modules\User\Models\User;

class CommentRepository
{
    public static function listByModel($model)
    {
        $comments = $model->comments()
            ->select([
                'comment.id',
                'user.id as user_id',
                'person.names as person',
                'user.avatar as photo',
                'comment.created_at as date',
                'comment.value',
            ])
            ->join('person', 'comment.person_id', 'person.id')
            ->join('user', 'person.id', 'user.person_id')
            ->orderBy('comment.created_at', 'desc')
            ->get();

        return $comments;
    }

    public static function get(int $id)
    {
        $comment = Comment::select([
            'comment.id',
            'user.id as user_id',
            'person.names as person',
            'user.avatar as photo',
            'comment.created_at as date',
            'comment.value',
        ])
            ->join('person', 'comment.person_id', '=', 'person.id')
            ->join('user', 'person.id', 'user.person_id')
            ->where('comment.id', $id)
            ->first();

        return $comment;
    }

    public static function create(Request $request)
    {
        $session = SessionManager::get();

        $user = User::find($session->id);

        CommentHelper::validateRequest($request);

        $models = [
            "content" => Content::class,
            "publication" => Publication::class,
        ];

        $morph = $models[$request->model]::find($request->model_id);

        $classroom_id = $morph->contentGroup?->classroom_id ?? $morph->classroom_id;
        ClassroomHelper::validateAccess($user->person_id, $classroom_id);
        PeriodHelper::validateByClassroom($classroom_id);

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

        $comment = Comment::byKey('id', $id);

        CommentHelper::validateRequest($request, $id);

        if ($comment->person_id !== $user->person_id) {
            throw new Exception('No tiene permisos para editar este comentario');
        }

        $morph = $comment->commentable;
        $classroom_id = $morph->contentGroup?->classroom_id ?? $morph->classroom_id;
        PeriodHelper::validateByClassroom($classroom_id);

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

        $comment = Comment::byKey('id', $id);

        if ($comment->person_id !== $user->person_id) {
            throw new Exception('No tiene permisos para eliminar este comentario');
        }

        $morph = $comment->commentable;
        $classroom_id = $morph->contentGroup?->classroom_id ?? $morph->classroom_id;
        PeriodHelper::validateByClassroom($classroom_id);

        $comment->delete();

        return 'Comentario eliminado correctamente';
    }
}
