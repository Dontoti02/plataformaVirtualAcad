<?php

namespace Modules\Tenant\Modules\Classroom\Repositories;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Shared\Helpers\SessionManager;
use Modules\Tenant\Modules\Classroom\Helpers\ClassroomHelper;
use Modules\Tenant\Modules\Classroom\Helpers\PublicationHelper;
use Modules\Tenant\Modules\Classroom\Models\Publication;
use Modules\Tenant\Modules\File\Repositories\FileRepository;
use Modules\Tenant\Modules\Management\Helpers\PeriodHelper;
use Modules\Tenant\Modules\User\Models\User;
use Modules\Tenant\Services\FileTenantService;

class PublicationRepository
{
    public static function list(Request $request)
    {
        PublicationHelper::validateListRequest($request);

        $publications = Publication::select([
            'publication.id',
            'user.id as user_id',
            'person.names as person',
            'user.avatar as photo',
            'publication.created_at as date',
            'publication.value',
        ])
            ->join('person', 'publication.person_id', 'person.id')
            ->join('user', 'person.id', 'user.person_id')
            ->where('publication.classroom_id', $request->classroom_id)
            ->orderBy('publication.created_at', 'desc')
            ->paginate($request->size, ['*'], 'page', $request->page);

        $aux = [];
        foreach ($publications->items() as $publication) {

            $aux[] = [
                ...$publication->toArray(),
                'files' => FileRepository::listByModel($publication),
                'comments' => CommentRepository::listByModel($publication),
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
        $publication = Publication::select([
            'publication.id',
            'user.id as user_id',
            'person.names as person',
            'user.avatar as photo',
            'publication.created_at as date',
            'publication.value',
        ])
            ->join('person', 'publication.person_id', '=', 'person.id')
            ->join('user', 'person.id', '=', 'user.person_id')
            ->where('publication.id', $id)
            ->first();

        $result = [
            ...$publication->toArray(),
            'files' => FileRepository::listByModel($publication),
            'comments' => CommentRepository::listByModel($publication),
        ];

        return $result;
    }

    public static function create(Request $request)
    {
        $session = SessionManager::get();

        $user = User::find($session->id);

        PublicationHelper::validateRequest($request);

        ClassroomHelper::validateAccess($user->person_id, $request->classroom_id);
        PeriodHelper::validateByClassroom($request->classroom_id);

        $publication = Publication::create([
            'classroom_id' => $request->classroom_id,
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

        $publication = Publication::byKey('id', $id);

        PublicationHelper::validateRequest($request, $id);

        if ($publication->person_id !== $user->person_id) {
            throw new Exception("No tienes permisos para editar esta publicación");
        }

        PeriodHelper::validateByClassroom($publication->classroom_id);

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

        $publication = Publication::byKey('id', $id);

        if ($publication->person_id !== $user->person_id) {
            throw new Exception("No tienes permisos para eliminar esta publicación");
        }

        PeriodHelper::validateByClassroom($publication->classroom_id);

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
