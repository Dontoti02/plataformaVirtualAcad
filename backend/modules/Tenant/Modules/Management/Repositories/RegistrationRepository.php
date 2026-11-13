<?php

namespace Modules\Tenant\Modules\Management\Repositories;

use Illuminate\Http\Request;
use Modules\Shared\Helpers\SessionManager;
use Modules\Tenant\Modules\Classroom\Models\Classroom;
use Modules\Tenant\Modules\Classroom\Models\Student;
use Modules\Tenant\Modules\Management\Helpers\PeriodHelper;
use Modules\Tenant\Modules\Management\Helpers\RegistrationHelper;
use Modules\Tenant\Modules\Management\Helpers\StudyProgramHelper;
use Modules\Tenant\Modules\Management\Models\Period;
use Modules\Tenant\Modules\User\Enums\RolTenant;
use Modules\Tenant\Modules\User\Models\Person;
use Modules\Tenant\Modules\User\Models\User;

class RegistrationRepository
{
    public static function filters()
    {
        $periods = PeriodHelper::list();
        $study_programs = StudyProgramHelper::list();

        $result = [
            'periods' => $periods,
            'study_programs' => $study_programs,
        ];

        return $result;
    }

    public static function listStudents(Request $request)
    {
        RegistrationHelper::validateListStudentsRequest($request);
        StudyProgramHelper::validateStudyProgramAccess((int) $request->study_program_id);

        $students = Student::select([
            'person.id',
            'person.document_number',
            'person.names',
        ])
            ->join('person', 'student.person_id', 'person.id')
            ->where('student.period_id', $request->period_id)
            ->where('student.study_program_id', $request->study_program_id)
            ->orderBy('person.names', 'asc')
            ->distinct()
            ->get();

        return $students;
    }

    public static function list(Request $request)
    {
        $session = SessionManager::get();

        $user = User::find($session->id);
        $is_student = $user->rol_id == RolTenant::STUDENT;

        RegistrationHelper::validateListRequest($request, $is_student);

        if (!$is_student) {
            StudyProgramHelper::validateStudentPersonAccess((int) $request->person_id, $user);
        }

        $person_id = $is_student ? $user->person_id : $request->person_id;

        $person = Person::byKey('id', $person_id);
        $period = Period::byKey('id', $request->period_id);

        $query = Classroom::select([
            'classroom.id',
            'course.name as course',
            'person.names as teacher',
            'shift.name as shift',
            'student.status',
            'student.registration_date',
        ])
            ->join('participant', 'classroom.id', 'participant.classroom_id')
            ->join('shift', 'classroom.shift_id', 'shift.id')
            ->join('course', 'classroom.course_id', 'course.id')
            ->join('study_program', 'course.study_program_id', 'study_program.id')
            ->leftJoin('teacher', 'classroom.id', 'teacher.classroom_id')
            ->leftJoin('person', 'teacher.person_id', 'person.id')
            ->leftJoin('student', function ($join) use ($request, $person_id) {
                $join->on('study_program.id', 'student.study_program_id')
                    ->where('student.period_id', $request->period_id)
                    ->where('student.person_id', $person_id);
            })
            ->where('classroom.period_id', $request->period_id)
            ->where('participant.person_id', $person_id);

        if (!$is_student) {
            StudyProgramHelper::scopeQueryByStudyProgram($query, 'course.study_program_id', $user);
        }

        $result = $query
            ->orderBy('course.name', 'asc')
            ->distinct()
            ->get();

        return [$result, $person, $period];
    }

    public static function consolidated(Request $request)
    {
        $session = SessionManager::get();

        $user = User::find($session->id);
        $is_student = $user->rol_id == RolTenant::STUDENT;

        RegistrationHelper::validateConsolidatedRequest($request, $is_student);

        if (!$is_student) {
            StudyProgramHelper::validateStudentPersonAccess((int) $request->person_id, $user);
        }

        $person_id = $is_student ? $user->person_id : $request->person_id;

        $person = Person::byKey('id', $person_id);

        $query = Student::selectRaw("
            student.id,
            period.name as period,
            cycle.name as cycle,
            student.registration_date,
            student.status,
            COUNT(participant.id) as total_courses
        ")
            ->join('period', 'student.period_id', 'period.id')
            ->join('cycle', 'student.cycle_id', 'cycle.id')
            ->join('study_program', 'student.study_program_id', 'study_program.id')
            ->join('course', 'study_program.id', 'course.study_program_id')
            ->join('classroom', 'course.id', 'classroom.course_id')
            ->join('participant', 'classroom.id', 'participant.classroom_id')
            ->where('student.person_id', $person_id)
            ->where('participant.person_id', $person_id)
            ->whereColumn('classroom.period_id', 'student.period_id');

        if (!$is_student) {
            StudyProgramHelper::scopeQueryByStudyProgram($query, 'student.study_program_id', $user);
        }

        $result = $query
            ->groupBy('student.id', 'period.name', 'cycle.name', 'student.registration_date')
            ->orderBy('period.name', 'asc')
            ->distinct()
            ->get();

        return [$result, $person];
    }
}
