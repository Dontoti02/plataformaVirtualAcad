<?php

namespace Modules\Tenant\Modules\Schedule\Repositories;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Shared\Helpers\SessionManager;
use Modules\Tenant\Modules\Classroom\Models\Classroom;
use Modules\Tenant\Modules\Classroom\Models\Participant;
use Modules\Tenant\Modules\Classroom\Models\Student;
use Modules\Tenant\Modules\Classroom\Models\Teacher;
use Modules\Tenant\Modules\Management\Enums\Shifts;
use Modules\Tenant\Modules\Management\Helpers\CycleHelper;
use Modules\Tenant\Modules\Management\Helpers\PeriodHelper;
use Modules\Tenant\Modules\Management\Helpers\StudyProgramHelper;
use Modules\Tenant\Modules\Management\Models\Course;
use Modules\Tenant\Modules\Management\Models\Period;
use Modules\Tenant\Modules\Management\Models\Section;
use Modules\Tenant\Modules\Management\Models\Staff;
use Modules\Tenant\Modules\Management\Models\StudyProgram;
use Modules\Tenant\Modules\Schedule\Helpers\ScheduleHelper;
use Modules\Tenant\Modules\Schedule\Models\Schedule;
use Modules\Tenant\Modules\SystemConfiguration\Helpers\SystemConfigurationHelper;
use Modules\Tenant\Modules\User\Enums\RolTenant;
use Modules\Tenant\Modules\User\Helpers\RolHelper;
use Modules\Tenant\Modules\User\Models\Person;
use Modules\Tenant\Modules\User\Models\Rol;
use Modules\Tenant\Modules\User\Models\User;

class ScheduleRepository
{
    public static function filters()
    {
        $study_programs = StudyProgramHelper::list();
        $cycles = CycleHelper::list();

        $days = SystemConfigurationHelper::getStudyDays();
        $hours = SystemConfigurationHelper::getStudyHours();

        $result = [
            'study_programs' => $study_programs,
            'cycles' => $cycles,
            'days' => $days,
            'hours' => $hours,
        ];

        return $result;
    }

    public static function filtersByExport(Request $request)
    {
        ScheduleHelper::validateFiltersByExportRequest($request);
        StudyProgramHelper::validateStudyProgramAccess((int) $request->study_program_id);

        $roles = Rol::select()
            ->whereIn('id', [RolTenant::TEACHER, RolTenant::STUDENT])
            ->get();

        foreach ($roles as $role) {
            if ($role->id == RolTenant::TEACHER) {
                $role->persons = Staff::selectRaw("
                    person.id, 
                    CONCAT(person.document_number, ' - ', person.names) AS names
                ")
                    ->join('person', 'staff.person_id', 'person.id')
                    ->where('staff.period_id', $request->period_id)
                    ->where('staff.type', 'DOCENTE')
                    ->orderBy('person.names', 'asc')
                    ->get();
            } else {
                $role->persons = Student::selectRaw("
                    person.id, 
                    CONCAT(person.document_number, ' - ', person.names) AS names
                ")
                    ->join('person', 'student.person_id', 'person.id')
                    ->where('student.period_id', $request->period_id)
                    ->where('student.study_program_id', $request->study_program_id)
                    ->where('student.cycle_id', $request->cycle_id)
                    ->orderBy('person.names', 'asc')
                    ->get();
            }
        }

        return $roles;
    }

    public static function list(Request $request)
    {
        $session = SessionManager::get();

        $user = User::find($session->id);
        $is_secretary = in_array($user->rol_id, [RolTenant::SECRETARY, RolTenant::ADMINISTRADOR, RolTenant::DIRECTOR_GENERAL, RolTenant::JEFE_UNIDAD_ACADEMICA, RolTenant::COORDINADOR_ACADEMICO]);
        $is_teacher = $user->rol_id ==  RolTenant::TEACHER;
        $is_student = $user->rol_id ==  RolTenant::STUDENT;

        ScheduleHelper::validateListRequest($request, $is_secretary);

        if ($is_secretary) {
            StudyProgramHelper::validateStudyProgramAccess((int) $request->study_program_id, $user);
        }

        $classrooms = Classroom::select([
            'classroom.id',
            'course.id as course_id',
            'course.name as course_name',
            'cycle.id as cycle_id',
            'cycle.name as cycle_name',
            'section.id as section_id',
            'section.name as section_name',
            'person.id as teacher_id',
            'person.names as teacher_name',
        ])
            ->join('schedule', 'classroom.id', 'schedule.classroom_id')
            ->join('course', 'classroom.course_id', 'course.id')
            ->join('cycle', 'course.cycle_id', 'cycle.id')
            ->join('section', 'classroom.section_id', 'section.id')
            ->where('classroom.period_id', $request->period_id)
            ->when($is_secretary, function ($query) use ($request) {
                $query->leftJoin('teacher', 'classroom.id', 'teacher.classroom_id')
                    ->leftJoin('person', 'teacher.person_id', 'person.id')
                    ->where('course.study_program_id', $request->study_program_id)
                    ->where('course.cycle_id', $request->cycle_id);
            })
            ->when($is_teacher, function ($query) use ($user) {
                $query->join('teacher', 'classroom.id', 'teacher.classroom_id')
                    ->join('person', 'teacher.person_id', 'person.id')
                    ->where('teacher.person_id', $user->person_id);
            })
            ->when($is_student, function ($query) use ($user) {
                $query->leftJoin('teacher', 'classroom.id', 'teacher.classroom_id')
                    ->leftJoin('person', 'teacher.person_id', 'person.id')
                    ->join('participant', 'classroom.id', 'participant.classroom_id')
                    ->where('participant.person_id', $user->person_id);
            })
            ->distinct()
            ->get();

        $result = [];
        foreach ($classrooms as $classroom) {

            $add['id'] = $classroom->id;
            $add['course'] = [
                'id' => $classroom->course_id,
                'name' => $classroom->course_name,
            ];
            $add['cycle'] = [
                'id' => $classroom->cycle_id,
                'name' => $classroom->cycle_name,
            ];
            $add['section'] = [
                'id' => $classroom->section_id,
                'name' => $classroom->section_name,
            ];
            $add['teacher'] = $classroom->teacher_id ? [
                'id' => $classroom->teacher_id,
                'name' => $classroom->teacher_name,
            ] : null;

            $days = Schedule::select([
                'id',
                'day',
                'hour_start',
                'hour_end',
            ])
                ->where('classroom_id', $classroom->id)
                ->get();
            $add['days'] = $days;

            if ($is_teacher) {
                $participants = Participant::select([
                    'participant.id',
                    'participant.person_id',
                    'person.names',
                    'person.email',
                ])
                    ->join('person', 'participant.person_id', 'person.id')
                    ->where('participant.classroom_id', $classroom->id)
                    ->orderBy('person.names', 'asc')
                    ->get();
                $add['participants'] = $participants;
            }

            $result[] = $add;
        }

        return $result;
    }

    public static function listClassrooms(Request $request)
    {
        ScheduleHelper::validateListClassroomsRequest($request);
        StudyProgramHelper::validateStudyProgramAccess((int) $request->study_program_id);

        $hours = SystemConfigurationHelper::getStudyHours();
        $shift_one = Shifts::ONE;

        $courses = Course::select([
            'course.id',
            'course.name',
        ])
            ->join('classroom', 'course.id', 'classroom.course_id')
            ->where('classroom.period_id', $request->period_id)
            ->where('course.study_program_id', $request->study_program_id)
            ->where('course.cycle_id', $request->cycle_id)
            ->orderBy('course.name', 'asc')
            ->distinct()
            ->get();

        foreach ($courses as $course) {
            $sections = Section::selectRaw("
                section.id,
                section.name,
                classroom.id as classroom_id,
                shift.name as shift,
                IF(shift.name = '$shift_one', '$hours->start', '12:00') as hour_start,
                IF(shift.name = '$shift_one', '12:00', '$hours->end') as hour_end,
                teacher.person_id as teacher_id
            ")
                ->join('classroom', 'section.id', 'classroom.section_id')
                ->join('shift', 'classroom.shift_id', 'shift.id')
                ->leftJoin('teacher', 'classroom.id', 'teacher.classroom_id')
                ->where('classroom.course_id', $course->id)
                ->where('classroom.period_id', $request->period_id)
                ->distinct()
                ->get();

            $course->sections = $sections;
        }

        return $courses;
    }

    public static function listClassroomsExisting(Request $request)
    {
        ScheduleHelper::validateListClassroomsRequest($request);
        StudyProgramHelper::validateStudyProgramAccess((int) $request->study_program_id);

        $hours = SystemConfigurationHelper::getStudyHours();
        $shift_one = Shifts::ONE;

        $courses = Course::select([
            'course.id',
            'course.name',
        ])
            ->join('classroom', 'course.id', 'classroom.course_id')
            ->whereExists(function ($query) use ($request) {
                $query->select(DB::raw(1))
                    ->from('schedule')
                    ->whereColumn('schedule.classroom_id', 'classroom.id');
            })
            ->where('classroom.period_id', $request->period_id)
            ->where('course.study_program_id', $request->study_program_id)
            ->where('course.cycle_id', $request->cycle_id)
            ->orderBy('course.name', 'asc')
            ->distinct()
            ->get();

        foreach ($courses as $course) {
            $sections = Section::selectRaw("
                section.id,
                section.name,
                classroom.id as classroom_id,
                shift.name as shift,
                IF(shift.name = '$shift_one', '$hours->start', '12:00') as hour_start,
                IF(shift.name = '$shift_one', '12:00', '$hours->end') as hour_end,
                teacher.person_id as teacher_id
            ")
                ->join('classroom', 'section.id', 'classroom.section_id')
                ->join('shift', 'classroom.shift_id', 'shift.id')
                ->leftJoin('teacher', 'classroom.id', 'teacher.classroom_id')
                ->where('classroom.course_id', $course->id)
                ->where('classroom.period_id', $request->period_id)
                ->distinct()
                ->get();

            $course->sections = $sections;
        }

        return $courses;
    }

    public static function listTeachers(Request $request)
    {
        ScheduleHelper::validateListTeachersRequest($request);

        $teachers = Staff::select([
            'person.id',
            'person.names',
        ])
            ->join('person', 'staff.person_id', 'person.id')
            ->where('staff.period_id', $request->period_id)
            ->where('staff.type', 'DOCENTE')
            ->orderBy('person.names', 'asc')
            ->distinct()
            ->get();

        return $teachers;
    }

    public static function create(Request $request)
    {
        RolHelper::validateSecretaryOrAdminAccess();

        ScheduleHelper::validateRequest($request);
        StudyProgramHelper::validateClassroomAccess((int) $request->classroom_id);

        $classroom = Classroom::find($request->classroom_id);

        // Validar que periodo sea el actual
        PeriodHelper::validateByClassroom($request->classroom_id);

         // Validar que no exista un horario para estas horas
         ScheduleHelper::validateNotExistsSchedule($classroom,'', $request->day,$request->hour_start,$request->hour_end);

        // Validar que las horas y minutos sean correctos
        ScheduleHelper::validateHoursAndMinutes($request);

        // // Validar el turno
        // ScheduleHelper::validateShift($classroom, $request);

        // Validar que no se cruce con otro horario
        ScheduleHelper::validateNotCross($classroom, $request);

        Schedule::create([
            'classroom_id' => $request->classroom_id,
            'day' => $request->day,
            'hour_start' => $request->hour_start,
            'hour_end' => $request->hour_end,
        ]);

        return "Horario creado correctamente";
    }

    public static function update(int $id, Request $request)
    {
        RolHelper::validateSecretaryOrAdminAccess();

        ScheduleHelper::validateRequest($request, true);

        $schedule = Schedule::byKey('id', $id);
        $classroom = $schedule->classroom;
        StudyProgramHelper::validateClassroomAccess((int) $classroom->id);

        // Validar que periodo sea el actual
        PeriodHelper::validateByClassroom($classroom->id);

        // Validar que las horas y minutos sean correctos
        ScheduleHelper::validateHoursAndMinutes($request);

        // Validar que no exista un horario para estas horas
        ScheduleHelper::validateNotExistsSchedule($classroom,$schedule->id ,$request->day,$request->hour_start,$request->hour_end);

        // Validar el turno
        // ScheduleHelper::validateShift($classroom, $request);

        // Validar que no se cruce con otro horario
        ScheduleHelper::validateNotCross($classroom, $request);

        $schedule->update([
            'day' => $request->day,
            'hour_start' => $request->hour_start,
            'hour_end' => $request->hour_end,
        ]);

        return "Horario actualizado correctamente";
    }

    public static function delete(int $id)
    {
        RolHelper::validateSecretaryOrAdminAccess();

        $schedule = Schedule::byKey('id', $id);
        StudyProgramHelper::validateClassroomAccess((int) $schedule->classroom_id);

        // Validar que periodo sea el actual
        PeriodHelper::validateByClassroom($schedule->classroom_id);

        $schedule->delete();

        return "Horario eliminado correctamente";
    }

    public static function assignTeacher(Request $request)
    {
        RolHelper::validateSecretaryOrAdminAccess();

        ScheduleHelper::validateAssignTeacherRequest($request);
        StudyProgramHelper::validateClassroomAccess((int) $request->classroom_id);

        $classroom = Classroom::find($request->classroom_id);

        // Validar que periodo sea el actual
        PeriodHelper::validateByClassroom($classroom->id);

        $teacher = $classroom->teacher;

        if ($teacher && $teacher->person_id == $request->person_id) {
            throw new Exception('Docente ya asignado a la clase');
        }

        // Validar que el docente no tenga otra clase asignada en el mismo horario
        ScheduleHelper::validateNotCrossByTeacher($classroom, $request);

        if ($teacher) {
            $teacher->update([
                'person_id' => $request->person_id,
            ]);
        } else {
            Teacher::create([
                'person_id' => $request->person_id,
                'classroom_id' => $classroom->id,
            ]);
        }

        return 'Docente asignado a la clase correctamente';
    }

    public static function listByExport(Request $request)
    {
        $session = SessionManager::get();

        $user = User::find($session->id);
        $is_secretary = in_array($user->rol_id, [RolTenant::SECRETARY, RolTenant::ADMINISTRADOR, RolTenant::DIRECTOR_GENERAL, RolTenant::JEFE_UNIDAD_ACADEMICA, RolTenant::COORDINADOR_ACADEMICO]);
        $is_teacher = $user->rol_id ==  RolTenant::TEACHER;
        $is_student = $user->rol_id ==  RolTenant::STUDENT;

        $person_id = $user->person_id;

        ScheduleHelper::validateListByExportRequest($request, $is_secretary);

        $period = Period::byKey('id', $request->period_id)->name;

        if ($is_secretary) {
            $is_teacher = $request->rol_id == RolTenant::TEACHER;
            $is_student = $request->rol_id == RolTenant::STUDENT;

            $person_id = $request->person_id;

            if ($is_student) {
                StudyProgramHelper::validateStudentPersonAccess((int) $person_id, $user);
            }
        }

        $rol = $is_teacher ? 'DOCENTE' : 'ALUMNO';

        $person = Person::byKey('id', $person_id);
        $person = $person->document_number . ' - ' . $person->names;

        if ($is_student) {
            $studentQuery = Student::select()
                ->where('period_id', $request->period_id)
                ->where('person_id', $person_id);
            StudyProgramHelper::scopeQueryByStudyProgram($studentQuery, 'student.study_program_id', $user);
            $student = $studentQuery->first();
            $studyProgram = $student ? StudyProgram::byKey('id', $student->study_program_id)->name : null;
        } else {
            $studyProgram = null;
        }

        $selectQuery = Classroom::select([
            'classroom.id',
            'course.name as course',
            'section.name as section',
            'person.names as teacher',
        ])
            ->join('schedule', 'classroom.id', 'schedule.classroom_id')
            ->join('course', 'classroom.course_id', 'course.id')
            ->join('section', 'classroom.section_id', 'section.id')
            ->where('classroom.period_id', $request->period_id)
            ->when($is_teacher, function ($query) use ($person_id) {
                $query->join('teacher', 'classroom.id', 'teacher.classroom_id')
                    ->join('person', 'teacher.person_id', 'person.id')
                    ->where('teacher.person_id', $person_id);
            })
            ->when($is_student, function ($query) use ($person_id) {
                $query->leftJoin('teacher', 'classroom.id', 'teacher.classroom_id')
                    ->leftJoin('person', 'teacher.person_id', 'person.id')
                    ->join('participant', 'classroom.id', 'participant.classroom_id')
                    ->where('participant.person_id', $person_id);
            });

        StudyProgramHelper::scopeQueryByStudyProgram($selectQuery, 'course.study_program_id', $user);

        $select = $selectQuery
            ->distinct()
            ->get();

        $selectMap = [];
        foreach ($select as $item) {

            $add['id'] = $item->id;

            $add['name'] = $item->course;
            $add['name'] .= '<br>Sección ' . $item->section;
            $add['name'] .= $is_student && $item->teacher ? '<br>Profesor: ' . ucwords(strtolower($item->teacher)) : '';

            $days = Schedule::select([
                'id',
                'day',
                'hour_start',
                'hour_end',
            ])
                ->where('classroom_id', $item->id)
                ->get();
            $add['days'] = $days;

            $selectMap[] = $add;
        }

        $result = (object) [
            'studyProgram' => $studyProgram,
            'rol' => $rol,
            'person' => $person,
            'period' => $period,
            'list' => $selectMap,
        ];

        return $result;
    }
}
