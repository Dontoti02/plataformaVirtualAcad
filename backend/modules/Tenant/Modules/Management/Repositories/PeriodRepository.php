<?php

namespace Modules\Tenant\Modules\Management\Repositories;

use Exception;
use Illuminate\Http\Request;
use Modules\Tenant\Modules\Classroom\Models\Classroom;
use Modules\Tenant\Modules\Classroom\Models\Student;
use Modules\Tenant\Modules\Management\Helpers\PeriodHelper;
use Modules\Tenant\Modules\Management\Helpers\StudyProgramHelper;
use Modules\Tenant\Modules\Management\Models\Period;
use Modules\Tenant\Modules\User\Helpers\RolHelper;

class PeriodRepository
{
    public static function list()
    {
        $periods = PeriodHelper::list();

        foreach ($periods as $period) {
            $period->classrooms = Classroom::select()
                ->where('period_id', $period->id)
                ->count();

            $period->students = Student::select()
                ->where('period_id', $period->id)
                ->count();
        }

        return $periods;
    }

    public static function current()
    {
        $period = PeriodHelper::current();

        if (!$period) {
            throw new Exception('No se encontraron periodos registrados');
        }

        return $period;
    }

    public static function disable(int $id)
    {
        RolHelper::validateSecretaryOrAdminAccess();

        $period = Period::byKey('id', $id);

        $status = $period->status ?? 'SIN TERMINO';
        $status = $status == 'TERMINADO' ? 'SIN TERMINO' : 'TERMINADO';

        $period->update(['status' => $status]);

        if ($status == 'SIN TERMINO') {
            Period::whereNot('id', $id)->update(['status' => 'TERMINADO']);
        }

        return "Estado del periodo $period->name actualizado correctamente";
    }

    public static function filtersClassrooms()
    {
        $studyProgram = StudyProgramHelper::list();

        $result = [
            'study_programs' => $studyProgram,
        ];

        return $result;
    }

    public static function listClassrooms(Request $request)
    {
        PeriodHelper::validateListClassroomsRequest($request);

        $classrooms = Classroom::select([
            'classroom.id',
            'study_program.name as study_program',
            'cycle.name as cycle',
            'course.name as course',
        ])
            ->join('course', 'course.id', 'classroom.course_id')
            ->join('study_program', 'study_program.id', 'course.study_program_id')
            ->join('cycle', 'cycle.id', 'course.cycle_id')
            ->where('classroom.period_id', $request->period_id)
            ->when($request->filled('study_program_id'), function ($query) use ($request) {
                $query->where('course.study_program_id', $request->study_program_id);
            })
            ->when($request->filled('search'), function ($query) use ($request) {
                $query->where('course.name', 'like', "%$request->search%");;
            })
            ->orderBy('course.name', 'asc')
            ->distinct()
            ->paginate($request->size, ['*'], 'page', $request->page);

        $result = [
            'page' => $request->page,
            'size' => $request->size,
            'total' => $classrooms->total(),
            'classrooms' => $classrooms->items(),
        ];

        return $result;
    }

    public static function updateDate(Request $request)
    {
        RolHelper::validateSecretaryOrAdminAccess();

        $period = Period::byKey('id', $request->period_id);

        $period->update([
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
        ]);

        return "Fechas del periodo $period->name actualizadas correctamente";
    }
}
