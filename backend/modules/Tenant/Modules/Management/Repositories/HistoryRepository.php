<?php

namespace Modules\Tenant\Modules\Management\Repositories;

use Illuminate\Http\Request;
use Modules\Shared\Helpers\SessionManager;
use Modules\Tenant\Modules\Classroom\Models\Student;
use Modules\Tenant\Modules\Management\Helpers\HistoryHelper;
use Modules\Tenant\Modules\Management\Helpers\StudyProgramHelper;
use Modules\Tenant\Modules\Management\Models\Period;
use Modules\Tenant\Modules\User\Enums\RolTenant;
use Modules\Tenant\Modules\User\Models\Person;
use Modules\Tenant\Modules\User\Models\User;

class HistoryRepository
{
    public static function filters()
    {
        $study_programs = StudyProgramHelper::list();

        $result = [
            'study_programs' => $study_programs,
        ];

        return $result;
    }

    public static function listStudents(Request $request)
    {
        HistoryHelper::validateListStudentsRequest($request);
        StudyProgramHelper::validateStudyProgramAccess((int) $request->study_program_id);

        $students = Student::select([
            'person.id',
            'person.document_number',
            'person.names',
        ])
            ->join('person', 'student.person_id', 'person.id')
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

        HistoryHelper::validateListRequest($request, $is_student);

        if (!$is_student) {
            StudyProgramHelper::validateStudentPersonAccess((int) $request->person_id, $user);
        }

        $person_id = $is_student ? $user->person_id : $request->person_id;

        $person = Person::byKey('id', $person_id);

        $result = Period::select('id', 'name')
            ->orderBy('period.name', 'desc')
            ->get();

        $accumulated_average = 0;
        foreach ($result as $index => $period) {
            $coursesQuery = $period->classrooms()
                ->selectRaw("
                        course.id,
                        course.name,
                        CAST(participant.score AS FLOAT) AS score
                    ")
                ->join('course', 'classroom.course_id', 'course.id')
                ->join('participant', 'classroom.id', 'participant.classroom_id')
                ->where('participant.person_id', $person_id);

            if (!$is_student) {
                StudyProgramHelper::scopeQueryByStudyProgram($coursesQuery, 'course.study_program_id', $user);
            }

            $courses = $coursesQuery
                ->orderBy('course.name', 'asc')
                ->get();

            $score_sum = $courses->sum('score');
            $score_count = $courses->count();

            $semester_average = $score_count > 0 ? $score_sum / $score_count : 0;
            $accumulated_average = ($accumulated_average + $semester_average) / ($index + 1);

            $period->semester_average = round($semester_average, 2);
            $period->accumulated_average = round($accumulated_average, 2);
            $period->courses = $courses;
        }

        return [$result, $person];
    }
}
