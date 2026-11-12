<?php

namespace Modules\Tenant\Modules\Classroom\Repositories;

use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Modules\Shared\Helpers\SessionManager;
use Modules\Tenant\Modules\Classroom\Helpers\AssistanceHelper;
use Modules\Tenant\Modules\Classroom\Helpers\ClassroomHelper;
use Modules\Tenant\Modules\Classroom\Models\Assistance;
use Modules\Tenant\Modules\Classroom\Models\Classroom;
use Modules\Tenant\Modules\Classroom\Models\Participant;
use Modules\Tenant\Modules\Management\Helpers\PeriodHelper;
use Modules\Tenant\Modules\User\Enums\RolTenant;
use Modules\Tenant\Modules\User\Models\User;

class AssistanceRepository
{
    public static function dates(int $classroom_id)
    {
        $session = SessionManager::get();

        $user = User::find($session->id);

        $classroom = Classroom::byKey('id', $classroom_id);

        ClassroomHelper::validateTeacherAccess($user->person_id, $classroom_id);

        $dates = $classroom->assistances()
            ->selectRaw("
                DATE(assistance.created_at) as date,
                CONCAT(SUM(IF(assistance.status IN ('attended', 'late'), 1, 0)), '/', COUNT(assistance.id)) as value
            ")
            ->groupByRaw('DATE(assistance.created_at)')
            ->orderByRaw('DATE(assistance.created_at) DESC')
            ->where('assistance.deleted_at', null)
            ->get();

        return $dates;
    }

    public static function list(Request $request)
    {
        $session = SessionManager::get();

        $user = User::find($session->id);
        $is_teacher = $user->rol_id == RolTenant::TEACHER;

        AssistanceHelper::validateListRequest($request, $is_teacher);

        $classroom = Classroom::byKey('id', $request->classroom_id);

        if ($is_teacher) {
            ClassroomHelper::validateTeacherAccess($user->person_id, $request->classroom_id);

            $assistances = $classroom->assistances()
                ->select([
                    'assistance.id',
                    'person.names as person',
                    'user.avatar as photo',
                    'assistance.status',
                    'assistance.date',
                    'assistance.reason',
                ])
                ->join('person', 'assistance.person_id', 'person.id')
                ->join('user', 'person.id', 'user.person_id')
                ->whereDate('assistance.created_at', $request->date)
                ->where('assistance.deleted_at', null)
                ->orderBy('person.names', 'asc')
                ->get();

            return $assistances;
        }

        ClassroomHelper::validateStudentAccess($user->person_id, $request->classroom_id);

        $assistances = $classroom->assistances()
            ->selectRaw("
                assistance.id,
                DATE(assistance.created_at) as date,
                assistance.status
            ")
            ->where('assistance.person_id', $user->person_id)
            ->orderBy('assistance.created_at', 'desc')
            ->get();

        return $assistances;
    }

    public static function create(Request $request)
    {
        $session = SessionManager::get();

        $user = User::find($session->id);

        AssistanceHelper::validateRequest($request);
        ClassroomHelper::validateTeacherAccess($user->person_id, $request->classroom_id);
        PeriodHelper::validateByClassroom($request->classroom_id);

        $count = Assistance::select()
            ->where('classroom_id', $request->classroom_id)
            ->whereDate('created_at', $request->date)
            ->where('deleted_at', null)
            ->count();

        if ($count) {
            throw new Exception('Ya se ha creado la asistencia para esa fecha');
        }

        $persons_id = Participant::select()
            ->where('classroom_id', $request->classroom_id)
            ->pluck('person_id')
            ->toArray();

        $date = Carbon::parse($request->date . ' ' . Carbon::now()->format('H:i:s'));

        $records = [];
        foreach ($persons_id as $person_id) {
            $records[] = [
                'classroom_id' => $request->classroom_id,
                'person_id' => $person_id,
                'status' => null,
                'date' => null,
                'reason' => null,
                'created_at' => $date,
                'updated_at' => $date,
            ];
        }

        Assistance::insert($records);

        $result = self::list($request);

        return $result;
    }

    public static function mark(int $id, Request $request)
    {
        $session = SessionManager::get();

        $user = User::find($session->id);

        $assistance = Assistance::byKey('id', $id);

        AssistanceHelper::validateMarkRequest($request);
        ClassroomHelper::validateTeacherAccess($user->person_id, $assistance->classroom_id);
        PeriodHelper::validateByClassroom($assistance->classroom_id);

        $date = Carbon::now();

        $assistance->update([
            'status' => $request->status,
            'date' => $date,
            'reason' => $request->status == 'late' && $request->reason ? $request->reason : null,
        ]);

        return 'Asistencia actualizada correctamente';
    }

    public static function absencesPerDay(Request $request)
    {
        $session = SessionManager::get();

        $user = User::find($session->id);

        AssistanceHelper::validateAbsencesPerDayRequest($request);
        ClassroomHelper::validateTeacherAccess($user->person_id, $request->classroom_id);

        $classroom = Classroom::byKey('id', $request->classroom_id);

        $result = $classroom->assistances()
            ->selectRaw("
                assistance.id,
                person.names as person,
                IF(assistance.status = 'attended', 1, 0) as attended,
                IF(assistance.status = 'absence', 1, 0) as absence,
                IF(assistance.status = 'late', 1, 0) as late,
                assistance.reason
            ")
            ->join('person', 'assistance.person_id', 'person.id')
            ->whereDate('assistance.created_at', $request->date)
            ->orderBy('person.names', 'asc')
            ->get();

        return [$result, $classroom, $request->date];
    }

    public static function consolidated(Request $request)
    {
        $session = SessionManager::get();

        $user = User::find($session->id);

        AssistanceHelper::validateAttendanceConsolidatedRequest($request);
        ClassroomHelper::validateTeacherAccess($user->person_id, $request->classroom_id);

        $classroom = Classroom::byKey('id', $request->classroom_id);

        $result = $classroom->assistances()
            ->selectRaw("
                DATE(assistance.created_at) as date,
                assistance.id,
                person.id as person_id,
                person.names as person,
                CASE
                    WHEN assistance.status = 'attended' THEN 'P'
                    WHEN assistance.status = 'absence' THEN 'F'
                    WHEN assistance.status = 'late' THEN 'T'
                    ELSE ''
                END as assistance
            ")
            ->join('person', 'assistance.person_id', 'person.id')
            ->orderBy('date', 'asc')
            ->orderBy('person.names', 'asc')
            ->get();

        $participants = $classroom->participants()
            ->selectRaw("
                person.id as id,
                person.names as person
            ")
            ->join('person', 'participant.person_id', 'person.id')
            ->orderBy('person.names', 'asc')
            ->get();

        return [$result, $classroom, $participants];
    }
    public static function deleteMultiple(Request $request)
    {
        $session = SessionManager::get();

        $user = User::find($session->id);

        AssistanceHelper::validateDeleteMultipleRequest($request);
        ClassroomHelper::validateTeacherAccess($user->person_id, $request->classroom_id);

        $classroom = Classroom::byKey('id', $request->classroom_id);

        $assistances = $classroom->assistances()
            ->whereDate('created_at', $request->date)
            ->where('classroom_id', $request->classroom_id)
            ->update(['deleted_at' => Carbon::now()]);
        $updatedAssistances = $classroom->assistances()
            ->select([
                'assistance.id',
                'person.names as person',
                'user.avatar as photo',
                'assistance.status',
                'assistance.date',
                'assistance.reason',
            ])
            ->join('person', 'assistance.person_id', 'person.id')
            ->join('user', 'person.id', 'user.person_id')
            ->whereDate('assistance.created_at', $request->date)
            ->where('assistance.deleted_at', null)
            ->orderBy('person.names', 'asc')
            ->get();

        return $updatedAssistances;
    }

}
