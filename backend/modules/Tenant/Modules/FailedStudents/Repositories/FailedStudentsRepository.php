<?php

namespace Modules\Tenant\Modules\FailedStudents\Repositories;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Tenant\Modules\FailedStudents\Helpers\FailedStudentsHelper;
use Modules\Tenant\Modules\Management\Helpers\CycleHelper;
use Modules\Tenant\Modules\Management\Helpers\PeriodHelper;
use Modules\Tenant\Modules\Management\Helpers\StudyProgramHelper;
use Modules\Tenant\Modules\Management\Models\Period;
use Modules\Tenant\Modules\Management\Models\StudyProgram;

class FailedStudentsRepository
{
    public static function filters(Request $request)
    {
        FailedStudentsHelper::validateAccess();
        FailedStudentsHelper::validateFilterRequest($request);

        if ($request->filled('study_program_id')) {
            StudyProgramHelper::validateStudyProgramAccess((int) $request->study_program_id);
        }
        if ($request->filled('classroom_id')) {
            StudyProgramHelper::validateClassroomAccess((int) $request->classroom_id);
        }

        $periods = Period::select('id', 'name', 'status')
            ->orderBy('name', 'desc')
            ->get();

        $selectedPeriodId = $request->filled('period_id')
            ? (int) $request->input('period_id')
            : self::resolveDefaultPeriodId($periods);

        $studyPrograms = StudyProgramHelper::list();

        $cycles = CycleHelper::list();

        $classroomsQuery = DB::table('classroom')
            ->join('course', 'classroom.course_id', '=', 'course.id')
            ->leftJoin('cycle', 'course.cycle_id', '=', 'cycle.id')
            ->leftJoin('section', 'classroom.section_id', '=', 'section.id')
            ->leftJoin('shift', 'classroom.shift_id', '=', 'shift.id')
            ->leftJoin('study_program', 'course.study_program_id', '=', 'study_program.id')
            ->whereNull('classroom.deleted_at')
            ->when($selectedPeriodId, fn ($q) => $q->where('classroom.period_id', $selectedPeriodId))
            ->when($request->filled('study_program_id'), fn ($q) => $q->where('course.study_program_id', $request->study_program_id))
            ->when($request->filled('cycle_id'), fn ($q) => $q->where('course.cycle_id', $request->cycle_id));

        StudyProgramHelper::scopeQueryByStudyProgram($classroomsQuery, 'course.study_program_id');

        $classroomsQuery = $classroomsQuery
            ->select([
                'classroom.id',
                'course.name as course_name',
                'study_program.name as program_name',
                'cycle.name as cycle_name',
                'section.name as section_name',
                'shift.name as shift_name',
            ])
            ->orderBy('course.name', 'asc')
            ->get()
            ->map(function ($c) {
                $details = array_filter([
                    $c->program_name,
                    $c->cycle_name ? "Ciclo {$c->cycle_name}" : null,
                    $c->section_name ? "Sec. {$c->section_name}" : null,
                    $c->shift_name,
                ]);

                return [
                    'id' => $c->id,
                    'name' => $c->course_name . ($details ? ' (' . implode(' · ', $details) . ')' : ''),
                    'course_name' => $c->course_name,
                    'program_name' => $c->program_name,
                ];
            });

        return [
            'periods' => $periods,
            'study_programs' => $studyPrograms,
            'cycles' => $cycles,
            'classrooms' => $classroomsQuery,
            'current_period_id' => $selectedPeriodId,
        ];
    }

    public static function list(Request $request)
    {
        FailedStudentsHelper::validateAccess();
        FailedStudentsHelper::validateFilterRequest($request);

        $baseQuery = self::buildBaseQuery($request);

        // Resumen general sobre unidades didácticas finalizadas o con notas registradas
        $totalStudents = (clone $baseQuery)->count('participant.id');
        $totalApproved = (clone $baseQuery)->where('participant.score', '>=', 13)->count('participant.id');
        $totalFailed = (clone $baseQuery)->where('participant.score', '<', 13)->count('participant.id');
        $failureRate = $totalStudents > 0 ? round(($totalFailed / $totalStudents) * 100, 1) : 0;
        $totalClassrooms = (clone $baseQuery)->distinct()->count('classroom.id');

        // Estudiantes no aprobados (< 13)
        $failedQuery = (clone $baseQuery)->where('participant.score', '<', 13);

        if ($request->filled('search')) {
            $search = trim($request->search);
            $failedQuery->where(function ($q) use ($search) {
                $q->where('student.names', 'like', "%{$search}%")
                    ->orWhere('student.document_number', 'like', "%{$search}%")
                    ->orWhere('course.name', 'like', "%{$search}%")
                    ->orWhere('doc.teacher_name', 'like', "%{$search}%");
            });
        }

        $students = $failedQuery->select([
            'participant.id as participant_id',
            'student.document_number',
            'student.names as student_name',
            'student.email as student_email',
            'student.phone as student_phone',
            'study_program.id as study_program_id',
            'study_program.name as study_program_name',
            'course.id as course_id',
            'course.name as course_name',
            'cycle.name as cycle_name',
            'section.name as section_name',
            'shift.name as shift_name',
            'doc.teacher_name',
            'participant.score',
            'classroom.id as classroom_id',
            'period.name as period_name',
        ])
            ->orderBy('study_program.name', 'asc')
            ->orderBy('course.name', 'asc')
            ->orderBy('student.names', 'asc')
            ->get()
            ->map(function ($item, $idx) {
                $sectionShift = array_filter([
                    $item->section_name ? "Sec. {$item->section_name}" : null,
                    $item->shift_name ? "Turno {$item->shift_name}" : null,
                ]);

                return [
                    'index' => $idx + 1,
                    'id' => $item->participant_id,
                    'document_number' => $item->document_number,
                    'student_name' => $item->student_name,
                    'student_email' => $item->student_email,
                    'student_phone' => $item->student_phone,
                    'study_program_name' => $item->study_program_name,
                    'course_name' => $item->course_name,
                    'cycle_name' => $item->cycle_name,
                    'section_shift' => implode(' / ', $sectionShift),
                    'teacher_name' => $item->teacher_name ?: 'Docente no asignado',
                    'score' => number_format((float) $item->score, 2, '.', ''),
                    'status' => 'DESAPROBADO',
                    'period_name' => $item->period_name,
                ];
            });

        return [
            'summary' => [
                'total_students' => $totalStudents,
                'total_approved' => $totalApproved,
                'total_failed' => $totalFailed,
                'failure_rate' => $failureRate,
                'total_classrooms' => $totalClassrooms,
            ],
            'students' => $students,
        ];
    }

    public static function buildBaseQuery(Request $request)
    {
        $teacherSubquery = DB::table('teacher')
            ->join('person', 'teacher.person_id', '=', 'person.id')
            ->select(
                'teacher.classroom_id',
                DB::raw("GROUP_CONCAT(DISTINCT person.names ORDER BY person.names SEPARATOR ', ') as teacher_name")
            )
            ->groupBy('teacher.classroom_id');

        $query = DB::table('participant')
            ->join('classroom', 'participant.classroom_id', '=', 'classroom.id')
            ->join('course', 'classroom.course_id', '=', 'course.id')
            ->join('study_program', 'course.study_program_id', '=', 'study_program.id')
            ->join('period', 'classroom.period_id', '=', 'period.id')
            ->join('cycle', 'course.cycle_id', '=', 'cycle.id')
            ->leftJoin('section', 'classroom.section_id', '=', 'section.id')
            ->leftJoin('shift', 'classroom.shift_id', '=', 'shift.id')
            ->join('person as student', 'participant.person_id', '=', 'student.id')
            ->leftJoinSub($teacherSubquery, 'doc', 'classroom.id', '=', 'doc.classroom_id')
            ->whereNull('participant.deleted_at')
            ->whereNull('classroom.deleted_at');

        self::applyCompletedClassroomScope($query);

        if ($request->filled('period_id')) {
            $query->where('classroom.period_id', $request->period_id);
        }

        if ($request->filled('study_program_id')) {
            StudyProgramHelper::validateStudyProgramAccess((int) $request->study_program_id);
            $query->where('course.study_program_id', $request->study_program_id);
        }

        if ($request->filled('cycle_id')) {
            $query->where('course.cycle_id', $request->cycle_id);
        }

        if ($request->filled('classroom_id')) {
            StudyProgramHelper::validateClassroomAccess((int) $request->classroom_id);
            $query->where('classroom.id', $request->classroom_id);
        }

        StudyProgramHelper::scopeQueryByStudyProgram($query, 'course.study_program_id');

        return $query;
    }

    /**
     * Incluye unidades didácticas cuyo periodo ya finalizó o que ya cuentan con
     * calificaciones registradas, evitando marcar como desaprobados a grupos sin notas.
     */
    private static function applyCompletedClassroomScope($query): void
    {
        $query->where(function ($scope) {
            $scope->where('period.status', 'TERMINADO')
                ->orWhereExists(function ($sub) {
                    $sub->select(DB::raw(1))
                        ->from('participant as p_eval')
                        ->whereColumn('p_eval.classroom_id', 'classroom.id')
                        ->whereNull('p_eval.deleted_at')
                        ->where('p_eval.score', '>', 0);
                })
                ->orWhereExists(function ($sub) {
                    $sub->select(DB::raw(1))
                        ->from('student_capacity as sc_eval')
                        ->join('capacity as cap_eval', 'sc_eval.capacity_id', '=', 'cap_eval.id')
                        ->whereColumn('cap_eval.classroom_id', 'classroom.id');
                });
        });
    }

    private static function resolveDefaultPeriodId($periods): ?int
    {
        $currentPeriod = PeriodHelper::current();
        if ($currentPeriod && self::periodHasEvaluatedClassrooms($currentPeriod->id)) {
            return (int) $currentPeriod->id;
        }

        foreach ($periods as $period) {
            if (self::periodHasEvaluatedClassrooms((int) $period->id)) {
                return (int) $period->id;
            }
        }

        return $currentPeriod?->id ?? ($periods->first()?->id ?? null);
    }

    private static function periodHasEvaluatedClassrooms(int $periodId): bool
    {
        $query = DB::table('classroom')
            ->join('course', 'classroom.course_id', '=', 'course.id')
            ->join('period', 'classroom.period_id', '=', 'period.id')
            ->join('participant', 'participant.classroom_id', '=', 'classroom.id')
            ->whereNull('classroom.deleted_at')
            ->whereNull('participant.deleted_at')
            ->where('classroom.period_id', $periodId);

        StudyProgramHelper::scopeQueryByStudyProgram($query, 'course.study_program_id');
        self::applyCompletedClassroomScope($query);

        return $query->exists();
    }
}
