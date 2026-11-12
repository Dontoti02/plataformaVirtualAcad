<?php

namespace Modules\Tenant\Modules\Schedule\Helpers;

use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Modules\Shared\Enum\DaysOfWeek;
use Modules\Tenant\Modules\Classroom\Models\Classroom;
use Modules\Tenant\Modules\Schedule\Models\Schedule;
use Modules\Tenant\Modules\SystemConfiguration\Helpers\SystemConfigurationHelper;

class ScheduleHelper
{
    public static function validateListRequest(Request $request, $is_secretary)
    {
        $required = $is_secretary ? "required" : "nullable";

        $validator = Validator::make($request->all(), [
            "period_id"         => "required|numeric|exists:period,id",
            "study_program_id"  => "$required|numeric|exists:study_program,id",
            "cycle_id"          => "$required|numeric|exists:cycle,id",
        ]);

        if ($validator->fails()) {
            throw new Exception($validator->errors()->first());
        }
    }

    public static function validateListClassroomsRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "period_id"         => "required|numeric|exists:period,id",
            "study_program_id"  => "required|numeric|exists:study_program,id",
            "cycle_id"          => "required|numeric|exists:cycle,id",
        ]);

        if ($validator->fails()) {
            throw new Exception($validator->errors()->first());
        }
    }

    public static function validateListTeachersRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "period_id" => "required|numeric|exists:period,id",
        ]);

        if ($validator->fails()) {
            throw new Exception($validator->errors()->first());
        }
    }

    public static function validateRequest(Request $request, $update = false)
    {
        $required = $update ? "nullable" : "required";

        $validator = Validator::make($request->all(), [
            "classroom_id"  => "$required|numeric|exists:classroom,id",
            "day"           => "required|numeric|in:0,1,2,3,4,5,6",
            "hour_start"    => "required|string|size:5",
            "hour_end"      => "required|string|size:5",
        ]);

        if ($validator->fails()) {
            throw new Exception($validator->errors()->first());
        }
    }

    public static function validateNotExistsSchedule($classroom,$request, $day,$hour_start, $hour_end)
    {
        $schedule = $classroom->schedules()
            ->where('id', '!=', $request)
            ->where('day', $day)
            ->where(function ($query) use ($hour_start, $hour_end) {
                $query->where(function ($query) use ($hour_start, $hour_end) {
                    $query->where('hour_start', '>=', $hour_start)
                        ->where('hour_end', '<=', $hour_end);
                })
                ->orWhere(function ($query) use ($hour_start, $hour_end) {
                    $query->where('hour_start', '<', $hour_end)
                        ->where('hour_end', '>', $hour_start);
                });
            })
            ->count();
        
        if ($schedule > 0) {
            throw new Exception("El horario hace conflicto con otro horario existente");
        }
    }

    public static function validateHoursAndMinutes(Request $request)
    {
        $hours = SystemConfigurationHelper::getStudyHours();
        $start = Carbon::createFromFormat('H:i', $hours->start);
        $end = Carbon::createFromFormat('H:i', $hours->end);

        $hour_start = Carbon::createFromFormat('H:i', $request->hour_start);
        $hour_end = Carbon::createFromFormat('H:i', $request->hour_end);

        $valid_minutes = [0,5,10, 15,20,25, 30,35,40, 45,50,55];

        if (!in_array($hour_start->minute, $valid_minutes)) {
            throw new Exception("Los minutos de la hora de inicio deben ser cada 5");
        }

        if (!in_array($hour_end->minute, $valid_minutes)) {
            throw new Exception("Los minutos de la hora de fin deben ser cada 5");
        }

        if ($hour_start->lt($start)) {
            throw new Exception("La hora de inicio debe ser mayor o igual a la hora de inicio de clases");
        }

        if ($hour_start->gte($end)) {
            throw new Exception("La hora de inicio debe ser menor a la hora de fin de clases");
        }

        if ($hour_start->gte($hour_end)) {
            throw new Exception("La hora de inicio debe ser menor a la hora de fin");
        }

        if ($hour_end->gt($end)) {
            throw new Exception("La hora de fin debe ser menor o igual a la hora de fin de clases");
        }

        if ($hour_start->diffInMinutes($hour_end) < 45) {
            throw new Exception("Se acepta mínimo 45 minutos por clase diaria");
        }

        if ($hour_start->diffInMinutes($hour_end) > 120) {
            throw new Exception("Se acepta máximo 2 horas por clase diaria");
        }
    }

    public static function validateShift($classroom, Request $request)
    {
        $hours = SystemConfigurationHelper::getStudyHours();
        $shift_one_start = Carbon::createFromFormat('H:i', $hours->start);
        $shift_one_end = Carbon::createFromFormat('H:i', '12:00');

        $shift_two_start = Carbon::createFromFormat('H:i', '12:00');
        $shift_two_end = Carbon::createFromFormat('H:i', $hours->end);

        $hour_start = Carbon::createFromFormat('H:i', $request->hour_start);
        $hour_end = Carbon::createFromFormat('H:i', $request->hour_end);

        if ($classroom->shift->name == 'MAÑANA') {
            if ($hour_start->lt($shift_one_start) || $hour_end->gt($shift_one_end)) {
                throw new Exception("El horario no corresponde al turno de la mañana");
            }
        } else if ($classroom->shift->name == 'TARDE') {
            if ($hour_start->lt($shift_two_start) || $hour_end->gt($shift_two_end)) {
                throw new Exception("El horario no corresponde al turno de la tarde");
            }
        } else {
            throw new Exception("Turno no contemplado");
        }
    }

    public static function validateNotCross($classroom, Request $request)
    {
        $hour_start = Carbon::createFromFormat('H:i', $request->hour_start);
        $hour_end = Carbon::createFromFormat('H:i', $request->hour_end);

        $classrooms = Classroom::select([
            'classroom.id',
            'course.name as course_name',
            'schedule.hour_start',
            'schedule.hour_end',
        ])
            ->join('course', 'classroom.course_id', 'course.id')
            ->join('schedule', 'classroom.id', 'schedule.classroom_id')
            ->where('classroom.id', '!=', $classroom->id)
            ->where('classroom.course_id', '!=', $classroom->course_id)
            ->where('classroom.period_id', $classroom->period_id)
            // Solo cruzan las clases del MISMO grupo de estudiantes: la mañana y la
            // tarde son grupos distintos. El cruce del docente se valida aparte.
            ->where('classroom.section_id', $classroom->section_id)
            ->where('classroom.shift_id', $classroom->shift_id)
            ->where('course.study_program_id', $classroom->course->study_program_id)
            ->where('course.cycle_id', $classroom->course->cycle_id)
            ->where('schedule.day', $request->day)
            ->get();

        foreach ($classrooms as $classroom) {
            $start = Carbon::createFromFormat('H:i', $classroom->hour_start);
            $end = Carbon::createFromFormat('H:i', $classroom->hour_end);

            if ($hour_start->lt($end) && $start->lt($hour_end)) {
                throw new Exception("El horario se cruza con la unidad didáctica $classroom->course_name");
            }
        }
    }

    public static function validateAssignTeacherRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'person_id'     => 'required|numeric|exists:person,id',
            'classroom_id'  => 'required|numeric|exists:classroom,id',
        ]);

        if ($validator->fails()) {
            throw new Exception($validator->errors()->first());
        }
    }

    /**
     * Compara los horarios de ESTA clase con los de las demás clases del docente.
     * Lo que ya tenga entre sus otras clases no impide esta asignación, y una
     * fila repetida en `teacher` no hace que la clase se cruce consigo misma.
     */
    public static function validateNotCrossByTeacher($classroom, Request $request)
    {
        $schedules_current = Schedule::where('classroom_id', $classroom->id)->get();

        $schedules_assign = Schedule::select([
            'schedule.day',
            'schedule.hour_start',
            'schedule.hour_end',
            'course.name as course_name',
            'section.name as section_name',
        ])
            ->join('classroom', 'schedule.classroom_id', 'classroom.id')
            ->join('course', 'classroom.course_id', 'course.id')
            ->leftJoin('section', 'classroom.section_id', 'section.id')
            ->join('teacher', 'classroom.id', 'teacher.classroom_id')
            ->where('teacher.person_id', $request->person_id)
            ->where('classroom.period_id', $classroom->period_id)
            ->where('classroom.id', '!=', $classroom->id)
            ->get();

        foreach ($schedules_current as $current) {
            foreach ($schedules_assign as $assigned) {
                $crosses = $current->day == $assigned->day
                    && $current->hour_start < $assigned->hour_end
                    && $assigned->hour_start < $current->hour_end;

                if ($crosses) {
                    $day = DaysOfWeek::DAYS[$assigned->day];
                    throw new Exception("Al docente se le cruza el horario con la unidad didáctica $assigned->course_name (sección $assigned->section_name) el día $day, de $assigned->hour_start a $assigned->hour_end");
                }
            }
        }
    }

    public static function validateFiltersByExportRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "period_id"         => "required|numeric|exists:period,id",
            "study_program_id"  => "required|numeric|exists:study_program,id",
            "cycle_id"          => "required|numeric|exists:cycle,id",
        ]);

        if ($validator->fails()) {
            throw new Exception($validator->errors()->first());
        }
    }

    public static function validateListByExportRequest(Request $request, $is_secretary)
    {
        $required = $is_secretary ? "required" : "nullable";

        $validator = Validator::make($request->all(), [
            "period_id"     => "required|numeric|exists:period,id",
            "rol_id"        => "$required|numeric|exists:rol,id",
            "person_id"     => "$required|numeric|exists:person,id",
        ]);

        if ($validator->fails()) {
            throw new Exception($validator->errors()->first());
        }
    }
}
