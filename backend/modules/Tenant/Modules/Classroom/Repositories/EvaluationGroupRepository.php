<?php

namespace Modules\Tenant\Modules\Classroom\Repositories;

use Exception;
use Illuminate\Http\Request;
use Modules\Shared\Helpers\SessionManager;
use Modules\Tenant\Modules\Classroom\Helpers\ClassroomHelper;
use Modules\Tenant\Modules\Classroom\Helpers\EvaluationGroupHelper;
use Modules\Tenant\Modules\Classroom\Models\Classroom;
use Modules\Tenant\Modules\Classroom\Models\EvaluationGroup;
use Modules\Tenant\Modules\Management\Helpers\PeriodHelper;
use Modules\Tenant\Modules\User\Models\User;

class EvaluationGroupRepository
{
    public static function list(int $classroom_id)
    {
        $classroom = Classroom::byKey('id', $classroom_id);

        $evaluation_groups = $classroom->evaluationGroups()
            ->select('id', 'title', 'weight')
            ->get();

        return $evaluation_groups;
    }

    public static function set(Request $request)
    {
        $session = SessionManager::get();

        $user = User::find($session->id);

        EvaluationGroupHelper::validateRequest($request);
        EvaluationGroupHelper::validateWeight($request);
        ClassroomHelper::validateTeacherAccess($user->person_id, $request->classroom_id);
        PeriodHelper::validateByClassroom($request->classroom_id);

        if ($request->filled('delete')) {
            $evaluation_groups = EvaluationGroup::select()
                ->whereIn('id', $request->delete)
                ->get();

            foreach ($evaluation_groups as $evaluation_group) {
                if ($evaluation_group->contents()->exists()) {
                    throw new Exception("No se puede eliminar el grupo de evaluación ($evaluation_group->title) porque tiene evaluaciones asociadas");
                }

                $evaluation_group->delete();
            }
        }

        if ($request->filled('update')) {
            foreach ($request->update as $item) {
                EvaluationGroupHelper::validateTitle($request->classroom_id, $item['title'], $item['id']);

                EvaluationGroup::find($item['id'])
                    ->update([
                        'title' => $item['title'],
                        'weight' => $item['weight']
                    ]);
            }
        }

        if ($request->filled('create')) {
            foreach ($request->create as $item) {
                EvaluationGroupHelper::validateTitle($request->classroom_id, $item['title']);

                EvaluationGroup::create([
                    'classroom_id' => $request->classroom_id,
                    'title' => $item['title'],
                    'weight' => $item['weight'],
                ]);
            }
        }

        $result = self::list($request->classroom_id);

        return $result;
    }
}
