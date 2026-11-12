<?php

namespace Modules\Tenant\Modules\MeritOrder\Repositories;

use Illuminate\Http\Request;
use Modules\Tenant\Modules\Classroom\Models\Participant;
use Modules\Tenant\Modules\Management\Helpers\CycleHelper;
use Modules\Tenant\Modules\Management\Models\Period;
use Modules\Tenant\Modules\Management\Models\StudyProgram;
use Modules\Tenant\Modules\MeritOrder\Helpers\MeritOrderHelper;
use Modules\Tenant\Modules\User\Helpers\RolHelper;

class MeritOrderRepository
{
    public static function filters()
    {
        $periods = Period::select()
            ->orderBy('name', 'desc')
            ->get();

        $study_programs = StudyProgram::select('id', 'name')
            ->orderBy('name', 'asc')
            ->get();

        $cycles = CycleHelper::list();

        $result = [
            'periods' => $periods,
            'study_programs' => $study_programs,
            'cycles' => $cycles,
        ];

        return $result;
    }

    public static function list(Request $request)
    {
        RolHelper::validateSecretaryOrAdminAccess();
        MeritOrderHelper::validateListRequest($request);

        $query = Participant::selectRaw("
            person.id,
            person.names,
            CAST((ROUND(SUM(participant.score) / COUNT(participant.id), 2)) AS FLOAT) AS semester_average
            ")
            ->join('person', 'participant.person_id', 'person.id')
            ->join('classroom', 'participant.classroom_id', 'classroom.id')
            ->join('course', 'classroom.course_id', 'course.id')
            ->where('course.study_program_id', $request->study_program_id)
            ->where('classroom.period_id', $request->period_id);

        if ($request->filled('cycle_id')) {
            $query->where('course.cycle_id', $request->cycle_id);
        }

        $select = $query->groupBy('person.id', 'person.names')
            ->orderBy('semester_average', 'desc')
            ->get();

        $select->each(function ($item, $key) {
            $item->order = $key + 1;
        });

        return $select;
    }
}
