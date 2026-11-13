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
            $classroomQuery = Classroom::select()
                ->join('course', 'classroom.course_id', 'course.id')
                ->where('classroom.period_id', $period->id);
            StudyProgramHelper::scopeQueryByStudyProgram($classroomQuery, 'course.study_program_id');
            $period->classrooms = $classroomQuery->count();

            $studentQuery = Student::select()
                ->where('period_id', $period->id);
            StudyProgramHelper::scopeQueryByStudyProgram($studentQuery, 'student.study_program_id');
            $period->students = $studentQuery->count();
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

        if ($request->filled('study_program_id')) {
            StudyProgramHelper::validateStudyProgramAccess((int) $request->study_program_id);
        }

        $query = Classroom::select([
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
            });

        StudyProgramHelper::scopeQueryByStudyProgram($query, 'course.study_program_id');

        $classrooms = $query
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
