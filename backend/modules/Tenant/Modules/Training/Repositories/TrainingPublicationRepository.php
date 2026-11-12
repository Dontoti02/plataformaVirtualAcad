<?php

namespace Modules\Tenant\Modules\Training\Repositories;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Shared\Helpers\SessionManager;
use Modules\Tenant\Modules\File\Repositories\FileRepository;
use Modules\Tenant\Modules\Management\Helpers\PeriodHelper;
use Modules\Tenant\Modules\Training\Helpers\TrainingHelper;
use Modules\Tenant\Modules\Training\Helpers\TrainingPublicationHelper;
use Modules\Tenant\Modules\Training\Models\Training;
use Modules\Tenant\Modules\Training\Models\TrainingPublication;
use Modules\Tenant\Modules\User\Models\User;
use Modules\Tenant\Services\FileTenantService;

class TrainingPublicationRepository
{
    public static function list(Request $request)
    {
        TrainingPublicationHelper::validateListRequest($request);

        $publications = TrainingPublication::select([
            'training_publication.id',
            'user.id as user_id',
            'person.names as person',
            'user.avatar as photo',
            'training_publication.created_at as date',
            'training_publication.value',
        ])
            ->join('person', 'training_publication.person_id', 'person.id')
            ->join('user', 'person.id', 'user.person_id')
            ->where('training_publication.training_id', $request->training_id)
            ->orderBy('training_publication.created_at', 'desc')
            ->paginate($request->size, ['*'], 'page', $request->page);

        $aux = [];
        foreach ($publications->items() as $publication) {

            $aux[] = [
                ...$publication->toArray(),
                'files' => FileRepository::listByModel($publication),
                'comments' => TrainingCommentRepository::listByModel($publication),
            ];
        }

        $result = [
            'page' => $request->page,
            'size' => $request->size,
            'total' => $publications->total(),
            'publications' => $aux,
        ];

        return $result;
    }

    public static function get(int $id)
    {
        $publication = TrainingPublication::select([
            'training_publication.id',
            'user.id as user_id',
            'person.names as person',
            'user.avatar as photo',
            'training_publication.created_at as date',
            'training_publication.value',
        ])
            ->join('person', 'training_publication.person_id', '=', 'person.id')
            ->join('user', 'person.id', '=', 'user.person_id')
            ->where('training_publication.id', $id)
            ->first();

        $result = [
            ...$publication->toArray(),
            'files' => FileRepository::listByModel($publication),
            'comments' => TrainingCommentRepository::listByModel($publication),
        ];

        return $result;
    }

    public static function create(Request $request)
    {
        $session = SessionManager::get();

        $user = User::find($session->id);

        TrainingPublicationHelper::validateRequest($request);

        TrainingHelper::validateAccess($user->person_id, $request->training_id);
        PeriodHelper::validateByTraining($request->training_id);

        $training = Training::findOrFail($request->training_id);

        TrainingHelper::checkTrainingStatus($user, $training, true);

        $publication = TrainingPublication::create([
            'training_id' => $request->training_id,
            'person_id' => $user->person_id,
            'value' => $request->value,
        ]);

        $result = self::get($publication->id);

        return $result;
    }

    public static function update(int $id, Request $request)
    {
        $session = SessionManager::get();

        $user = User::find($session->id);

        $publication = TrainingPublication::byKey('id', $id);

        TrainingPublicationHelper::validateRequest($request, $id);

        if ($publication->person_id !== $user->person_id) {
            throw new Exception("No tienes permisos para editar esta publicación");
        }

        PeriodHelper::validateByTraining($publication->training_id);

        $training = Training::findOrFail($publication->training_id);

        TrainingHelper::checkTrainingStatus($user, $training, true);

        $publication->update([
            'value' => $request->value,
        ]);

        $result = self::get($publication->id);

        return $result;
    }

    public static function delete(int $id)
    {
        $session = SessionManager::get();

        $user = User::find($session->id);

        $publication = TrainingPublication::byKey('id', $id);

        if ($publication->person_id !== $user->person_id) {
            throw new Exception("No tienes permisos para eliminar esta publicación");
        }

        PeriodHelper::validateByTraining($publication->training_id);

        $training = Training::findOrFail($publication->training_id);

        TrainingHelper::checkTrainingStatus($user, $training, true);

        $files = $publication->files;
        FileTenantService::delete($files->all());

        DB::beginTransaction();
        try {
            $publication->delete();
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }

        return 'Publicación eliminada correctamente';
    }
}
