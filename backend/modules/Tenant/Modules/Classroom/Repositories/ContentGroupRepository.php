<?php

namespace Modules\Tenant\Modules\Classroom\Repositories;

use Exception;
use Illuminate\Http\Request;
use Modules\Shared\Helpers\SessionManager;
use Modules\Tenant\Modules\Classroom\Helpers\ClassroomHelper;
use Modules\Tenant\Modules\Classroom\Helpers\ContentGroupHelper;
use Modules\Tenant\Modules\Classroom\Models\Classroom;
use Modules\Tenant\Modules\Classroom\Models\ContentGroup;
use Modules\Tenant\Modules\Management\Helpers\PeriodHelper;
use Modules\Tenant\Modules\User\Models\User;

class ContentGroupRepository
{
    public static function list(int $classroom_id)
    {
        $classroom = Classroom::byKey('id', $classroom_id);

        $content_groups = $classroom->contentGroups()
            ->select('id', 'title')
            ->whereNot('title', 'Sílabo')
            ->get();

        return $content_groups;
    }

    public static function create(Request $request)
    {
        $session = SessionManager::get();

        $user = User::find($session->id);

        ContentGroupHelper::validateRequest($request);
        ClassroomHelper::validateTeacherAccess($user->person_id, $request->classroom_id);
        PeriodHelper::validateByClassroom($request->classroom_id);

        $content_group = ContentGroup::create([
            'classroom_id' => $request->classroom_id,
            'title' => $request->title,
        ]);

        return $content_group;
    }

    public static function update(int $id, Request $request)
    {
        $session = SessionManager::get();

        $user = User::find($session->id);

        $content_group = ContentGroup::byKey('id', $id);

        if ($content_group->title == 'Sílabo') {
            throw new Exception('No se puede modificar el grupo de contenido Sílabo');
        }

        $request->merge(['classroom_id' => $content_group->classroom_id]);

        ContentGroupHelper::validateRequest($request, $id);
        ClassroomHelper::validateTeacherAccess($user->person_id, $request->classroom_id);
        PeriodHelper::validateByClassroom($request->classroom_id);

        $content_group->update([
            'title' => $request->title,
        ]);

        return $content_group;
    }

    public static function delete(int $id)
    {
        $session = SessionManager::get();

        $user = User::find($session->id);

        $content_group = ContentGroup::byKey('id', $id);

        if ($content_group->title == 'Sílabo') {
            throw new Exception('No se puede eliminar el grupo de contenido Sílabo');
        }

        ClassroomHelper::validateTeacherAccess($user->person_id, $content_group->classroom_id);
        PeriodHelper::validateByClassroom($content_group->classroom_id);

        if ($content_group->contents()->exists()) {
            throw new Exception('No se puede eliminar el grupo de contenido porque tiene contenido asociado');
        }

        $content_group->delete();

        return 'Grupo de contenido eliminado correctamente';
    }
}
