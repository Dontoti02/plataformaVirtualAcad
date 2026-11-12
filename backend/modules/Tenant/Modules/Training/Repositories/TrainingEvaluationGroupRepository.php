<?php

namespace Modules\Tenant\Modules\Training\Repositories;

use Exception;
use Illuminate\Http\Request;
use Modules\Shared\Helpers\SessionManager;
use Modules\Tenant\Modules\Management\Helpers\PeriodHelper;
use Modules\Tenant\Modules\Training\Helpers\TrainingEvaluationGroupHelper;
use Modules\Tenant\Modules\Training\Helpers\TrainingHelper;
use Modules\Tenant\Modules\Training\Models\Training;
use Modules\Tenant\Modules\Training\Models\TrainingEvaluationGroup;
use Modules\Tenant\Modules\User\Models\User;

class TrainingEvaluationGroupRepository
{
    public static function list(int $training_id)
    {
        $training = Training::byKey('id', $training_id);

        $evaluation_groups = $training->evaluationGroups()
            ->select('id', 'title', 'weight')
            ->get();

        return $evaluation_groups;
    }

    public static function set(Request $request)
    {
        $session = SessionManager::get();

        $user = User::find($session->id);

        TrainingEvaluationGroupHelper::validateRequest($request);
        TrainingEvaluationGroupHelper::validateWeight($request);
        TrainingHelper::validateTeacherAccess($user->person_id, $request->training_id);
        PeriodHelper::validateByTraining($request->training_id);

        if ($request->filled('delete')) {
            $evaluation_groups = TrainingEvaluationGroup::select()
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
                TrainingEvaluationGroupHelper::validateTitle($request->training_id, $item['title'], $item['id']);

                TrainingEvaluationGroup::find($item['id'])
                    ->update([
                        'title' => $item['title'],
                        'weight' => $item['weight']
                    ]);
            }
        }

        if ($request->filled('create')) {
            foreach ($request->create as $item) {
                TrainingEvaluationGroupHelper::validateTitle($request->training_id, $item['title']);

                TrainingEvaluationGroup::create([
                    'training_id' => $request->training_id,
                    'title' => $item['title'],
                    'weight' => $item['weight'],
                ]);
            }
        }

        $result = self::list($request->training_id);

        return $result;
    }
}
