<?php

namespace Modules\Tenant\Modules\Home\Repositories;

use Modules\Tenant\Modules\Classroom\Models\Assistance;
use Modules\Tenant\Modules\Classroom\Models\Classroom;
use Modules\Tenant\Modules\Classroom\Models\Student;
use Modules\Tenant\Modules\Management\Helpers\PeriodHelper;
use Modules\Tenant\Modules\Management\Helpers\StudyProgramHelper;
use Modules\Tenant\Modules\Management\Models\Staff;
use Modules\Tenant\Modules\Management\Models\StudyProgram;
use Modules\Tenant\Modules\SystemConfiguration\Helpers\SystemConfigurationHelper;
use Modules\Tenant\Modules\User\Helpers\RolHelper;

class HomeSecretaryRepository
{
    public static function home()
    {
        RolHelper::validateSecretaryOrAdminAccess();

        $period = PeriodHelper::current();

        if ($period) {
            $study_programs = self::getStudyPrograms($period->id);
            $classrooms = self::getClassrooms($period->id);
            $students = self::getStudents($period->id);
            $teachers = self::getTeachers($period->id);
        } else {
            $alerts = ['imports' => SystemConfigurationHelper::getAlertsImports()];
        }

        $summary = [
            [
                'title' => 'Unidades didácticas activas',
                'value' => $period ? $classrooms->total : 0,
            ],
            [
                'title' => 'Alumnos inscritos',
                'value' => $period ? $students->total : 0,
            ],
            [
                'title' => 'Profesores inscritos',
                'value' => $period ? $teachers->total : 0,
            ],
            [
                'title' => 'Periodo actual',
                'value' => $period ? $period->name : 'Sin periodo',
            ],
        ];

        $result = [
            'alerts' => $alerts ?? [],
            'summary' => $summary,
            'study_programs' => $period ? $study_programs->items : [],
        ];

        return $result;
    }

    public static function getStudyPrograms(int $period_id)
    {
        $study_programs = StudyProgramHelper::list();

        foreach ($study_programs as $study_program) {
            $query = $study_program->courses();
            $query->selectRaw("
                classroom.id,
                course.name,
                person.names as teacher,
                (
                    SELECT COUNT(*) 
                    FROM participant 
                    WHERE classroom_id = classroom.id
                ) as students
            ");
            $query->join('classroom', 'course.id', 'classroom.course_id');
            $query->leftJoin('teacher', 'classroom.id', 'teacher.classroom_id');
            $query->leftJoin('person', 'teacher.person_id', 'person.id');
            $query->where('classroom.period_id', $period_id);
            $query->orderBy('students', 'desc');
            $query->distinct();

            $total_classrooms = $query->count();
            $top_classrooms =  $query->limit(5)->get();

            foreach ($top_classrooms as $top_classroom) {
                $top_classroom->assistants = Assistance::selectRaw("
                    DATE(created_at) as date,
                    COUNT(*) as count
                ")
                    ->where('classroom_id', $top_classroom->id)
                    ->whereIn('status', ['attended', 'late'])
                    ->groupByRaw('DATE(created_at)')
                    ->orderByRaw('DATE(created_at) desc')
                    ->limit(5)
                    ->get();
            }

            $study_program->total_classrooms = $total_classrooms;
            $study_program->top_classrooms = $top_classrooms;
        }

        $result = (object) [
            'items' => $study_programs,
        ];

        return $result;
    }

    public static function getClassrooms(int $period_id)
    {
        $query = Classroom::select()
            ->join('course', 'classroom.course_id', 'course.id')
            ->where('classroom.period_id', $period_id);

        StudyProgramHelper::scopeQueryByStudyProgram($query, 'course.study_program_id');

        $total = $query->count();

        $result = (object) [
            'total' => $total,
        ];

        return $result;
    }

    public static function getStudents(int $period_id)
    {
        $query = Student::select()
            ->where('period_id', $period_id);

        StudyProgramHelper::scopeQueryByStudyProgram($query, 'student.study_program_id');

        $total = $query->count();

        $result = (object) [
            'total' => $total,
        ];

        return $result;
    }

    public static function getTeachers(int $period_id)
    {
        $programIds = StudyProgramHelper::assignedStudyProgramIds();

        $query = Staff::select()
            ->where('period_id', $period_id)
            ->where('type', 'DOCENTE');

        if ($programIds !== null) {
            if (empty($programIds)) {
                $query->whereRaw('1 = 0');
            } else {
                $query->whereExists(function ($sub) use ($period_id, $programIds) {
                    $sub->select(\Illuminate\Support\Facades\DB::raw(1))
                        ->from('teacher')
                        ->join('classroom', 'teacher.classroom_id', '=', 'classroom.id')
                        ->join('course', 'classroom.course_id', '=', 'course.id')
                        ->whereColumn('teacher.person_id', 'staff.person_id')
                        ->where('classroom.period_id', $period_id)
                        ->whereIn('course.study_program_id', $programIds);
                });
            }
        }

        $total = $query->count();

        $result = (object) [
            'total' => $total,
        ];

        return $result;
    }
}
