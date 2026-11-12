<?php

namespace Modules\Tenant\Modules\Training\Repositories;

use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Modules\Shared\Helpers\SessionManager;
use Modules\Tenant\Modules\Management\Helpers\PeriodHelper;
use Modules\Tenant\Modules\Training\Helpers\TrainingAssistanceHelper;
use Modules\Tenant\Modules\Training\Helpers\TrainingHelper;
use Modules\Tenant\Modules\Training\Models\Training;
use Modules\Tenant\Modules\Training\Models\TrainingAssistance;
use Modules\Tenant\Modules\Training\Models\TrainingParticipant;
use Modules\Tenant\Modules\User\Enums\RolTenant;
use Modules\Tenant\Modules\User\Models\User;

class TrainingAssistanceRepository
{
    public static function dates(int $training_id)
    {
        $session = SessionManager::get();

        $user = User::find($session->id);

        $training = Training::byKey('id', $training_id);

        TrainingHelper::validateTeacherAccess($user->person_id, $training_id);

        $dates = $training->assistances()
            ->selectRaw("
                DATE(training_assistance.created_at) as date,
                CONCAT(SUM(IF(training_assistance.status IN ('attended', 'late'), 1, 0)), '/', COUNT(training_assistance.id)) as value
            ")
            ->groupByRaw('DATE(training_assistance.created_at)')
            ->orderByRaw('DATE(training_assistance.created_at) DESC')
            ->where('training_assistance.deleted_at', null)
            ->get();

        return $dates;
    }

    public static function list(Request $request)
    {
        $session = SessionManager::get();

        $user = User::find($session->id);
        $is_teacher = $user->rol_id == RolTenant::TRAINING_TEACHER;

        TrainingAssistanceHelper::validateListRequest($request, $is_teacher);

        $training = Training::byKey('id', $request->training_id);

        if ($is_teacher) {
            TrainingHelper::validateTeacherAccess($user->person_id, $request->training_id);

            $assistances = $training->assistances()
                ->select([
                    'training_assistance.id',
                    'person.names as person',
                    'user.avatar as photo',
                    'training_assistance.status',
                    'training_assistance.date',
                    'training_assistance.reason',
                ])
                ->join('person', 'training_assistance.person_id', 'person.id')
                ->join('user', 'person.id', 'user.person_id')
                ->whereDate('training_assistance.created_at', $request->date)
                ->where('training_assistance.deleted_at', null)
                ->orderBy('person.names', 'asc')
                ->get();

            return $assistances;
        }

        TrainingHelper::validateStudentAccess($user->person_id, $request->training_id);

        $assistances = $training->assistances()
            ->selectRaw("
                training_assistance.id,
                DATE(training_assistance.created_at) as date,
                training_assistance.status
            ")
            ->where('training_assistance.person_id', $user->person_id)
            ->orderBy('training_assistance.created_at', 'desc')
            ->get();

        return $assistances;
    }

    public static function create(Request $request)
    {
        $session = SessionManager::get();

        $user = User::find($session->id);

        TrainingAssistanceHelper::validateRequest($request);
        TrainingHelper::validateTeacherAccess($user->person_id, $request->training_id);
        PeriodHelper::validateByTraining($request->training_id);

        $training = Training::findOrFail($request->training_id);

        TrainingHelper::checkTrainingStatus($user, $training, true);

        $count = TrainingAssistance::select()
            ->where('training_id', $request->training_id)
            ->whereDate('created_at', $request->date)
            ->where('deleted_at', null)
            ->count();

        if ($count) {
            throw new Exception('Ya se ha creado la asistencia para esa fecha');
        }

        $persons_id = TrainingParticipant::select()
            ->where('training_id', $request->training_id)
            ->pluck('person_id')
            ->toArray();

        $date = Carbon::parse($request->date . ' ' . Carbon::now()->format('H:i:s'));

        $records = [];
        foreach ($persons_id as $person_id) {
            $records[] = [
                'training_id' => $request->training_id,
                'person_id' => $person_id,
                'status' => null,
                'date' => null,
                'reason' => null,
                'created_at' => $date,
                'updated_at' => $date,
            ];
        }

        TrainingAssistance::insert($records);

        $result = self::list($request);

        return $result;
    }

    public static function mark(int $id, Request $request)
    {
        $session = SessionManager::get();

        $user = User::find($session->id);

        $assistance = TrainingAssistance::byKey('id', $id);

        TrainingAssistanceHelper::validateMarkRequest($request);
        TrainingHelper::validateTeacherAccess($user->person_id, $assistance->training_id);
        PeriodHelper::validateByTraining($assistance->training_id);

        $date = Carbon::now();

        $assistance->update([
            'status' => $request->status,
            'date' => $date,
            'reason' => $request->status == 'late' && $request->reason ? $request->reason : null,
        ]);

        $participant = TrainingParticipant::select()
            ->where('training_id', $assistance->training_id)
            ->where('person_id', $assistance->person_id)
            ->first();

        $training = Training::findOrFail($assistance->training_id);

        TrainingHelper::checkTrainingStatus($user, $training, true);

        $absences = TrainingAssistance::select()
            ->where('training_id', $assistance->training_id)
            ->where('person_id', $assistance->person_id)
            ->where('status', 'absence')
            ->count();

        $isActive = $participant->is_active;

        if ($training->num_max_absences == 0 || $absences <= $training->num_max_absences) {
            $isActive = true;
        } else {
            $isActive = false;
        }

        if ($participant->is_active != $isActive) {
            $participant->update([
                'is_active' => $isActive,
            ]);
        }

        return 'Asistencia actualizada correctamente';
    }

    public static function absencesPerDay(Request $request)
    {
        $session = SessionManager::get();

        $user = User::find($session->id);

        TrainingAssistanceHelper::validateAbsencesPerDayRequest($request);
        TrainingHelper::validateTeacherAccess($user->person_id, $request->training_id);

        $training = Training::byKey('id', $request->training_id);

        $result = $training->assistances()
            ->selectRaw("
                training_assistance.id,
                person.names as person,
                IF(training_assistance.status = 'attended', 1, 0) as attended,
                IF(training_assistance.status = 'absence', 1, 0) as absence,
                IF(training_assistance.status = 'late', 1, 0) as late,
                training_assistance.reason
            ")
            ->join('person', 'training_assistance.person_id', 'person.id')
            ->whereDate('training_assistance.created_at', $request->date)
            ->orderBy('person.names', 'asc')
            ->get();

        return [$result, $training, $request->date];
    }

    public static function consolidated(Request $request)
    {
        $session = SessionManager::get();

        $user = User::find($session->id);

        TrainingAssistanceHelper::validateAttendanceConsolidatedRequest($request);
        TrainingHelper::validateTeacherAccess($user->person_id, $request->training_id);

        $training = Training::byKey('id', $request->training_id);

        $result = $training->assistances()
            ->selectRaw("
                DATE(training_assistance.created_at) as date,
                training_assistance.id,
                person.id as person_id,
                person.names as person,
                CASE
                    WHEN training_assistance.status = 'attended' THEN 'P'
                    WHEN training_assistance.status = 'absence' THEN 'F'
                    WHEN training_assistance.status = 'late' THEN 'T'
                    ELSE ''
                END as assistance
            ")
            ->join('person', 'training_assistance.person_id', 'person.id')
            ->orderBy('date', 'asc')
            ->orderBy('person.names', 'asc')
            ->get();

        $participants = $training->participants()
            ->selectRaw("
                person.id as id,
                person.names as person
            ")
            ->join('person', 'training_participant.person_id', 'person.id')
            ->orderBy('person.names', 'asc')
            ->get();

        return [$result, $training, $participants];
    }

    public static function deleteMultiple(Request $request)
    {
        $session = SessionManager::get();

        $user = User::find($session->id);

        TrainingAssistanceHelper::validateDeleteMultipleRequest($request);
        TrainingHelper::validateTeacherAccess($user->person_id, $request->training_id);

        $training = Training::byKey('id', $request->training_id);

        TrainingHelper::checkTrainingStatus($user, $training, true);

        $training->assistances()
            ->whereDate('created_at', $request->date)
            ->where('training_id', $request->training_id)
            ->update(['deleted_at' => Carbon::now()]);

        $updatedAssistances = $training->assistances()
            ->select([
                'training_assistance.id',
                'person.names as person',
                'user.avatar as photo',
                'training_assistance.status',
                'training_assistance.date',
                'training_assistance.reason',
            ])
            ->join('person', 'training_assistance.person_id', 'person.id')
            ->join('user', 'person.id', 'user.person_id')
            ->whereDate('training_assistance.created_at', $request->date)
            ->where('training_assistance.deleted_at', null)
            ->orderBy('person.names', 'asc')
            ->get();

        return $updatedAssistances;
    }
}
