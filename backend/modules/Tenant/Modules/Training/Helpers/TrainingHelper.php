<?php

namespace Modules\Tenant\Modules\Training\Helpers;

use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Modules\Tenant\Modules\Training\Enums\TrainingStatusEnum;
use Modules\Tenant\Modules\Training\Models\Training;
use Modules\Tenant\Modules\Training\Models\TrainingParticipant;
use Modules\Tenant\Modules\Training\Models\TrainingTeacher;
use Modules\Tenant\Modules\User\Enums\RolTenant;
use Modules\Tenant\Modules\User\Models\User;


class TrainingHelper
{
    public static function validateListRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "page"                  => "required|integer|gt:0",
            "size"                  => "required|integer|gt:0",
            "search"                => "nullable|string",
            "only_completed"        => "nullable|boolean",
            "training_category_id"  => "nullable|integer|exists:training_category,id",
        ]);

        if ($validator->fails()) {
            throw new Exception($validator->errors()->first());
        }
    }

    public static function getMinutesRemaining(Training $training)
    {
        $now = Carbon::now();
        $createdAt = Carbon::parse($training->created_at);
        $startDate = Carbon::parse($training->start_date);
        $endDate = Carbon::parse($training->end_date);

        $statusId = $training->training_status_id;
        $minutesRemaining = round($now->diffInMinutes($startDate));
        $totalMinutes = round($createdAt->diffInMinutes($startDate));

        $startDateAddDays = $startDate->copy()->addDays(3);
        if ($startDateAddDays->greaterThanOrEqualTo($endDate->copy()->subDay())) {
            $startDateAddDays = $endDate->copy()->subDay();
        }

        $endDateAddDays = $endDate->copy()->addDays(3);

        if ($now->between($startDate, $endDate)) {
            $statusId = TrainingStatusEnum::IN_PROGRESS;
            $minutesRemaining = round($now->diffInMinutes($endDate));
            $totalMinutes = round($startDate->diffInMinutes($endDate));

            if (
                $training->participants()->count() < $training->min_participants ||
                (
                    $training->training_status_id === TrainingStatusEnum::NOT_STARTED &&
                    $now->between($startDate, $startDateAddDays)
                )
            ) {
                $statusId = TrainingStatusEnum::NOT_STARTED;
                $minutesRemaining = 0;
                $totalMinutes = null;
            }
        }

        if ($now->greaterThan($endDate)) {
            $statusId = TrainingStatusEnum::COMPLETED;
            $minutesRemaining = null;
            $totalMinutes = null;

            if (
                $training->training_status_id === TrainingStatusEnum::IN_PROGRESS &&
                $now->between($endDate, $endDateAddDays)
            ) {
                $statusId = TrainingStatusEnum::IN_PROGRESS;
                $minutesRemaining = 0;
                $totalMinutes = null;
            }
        }

        if ($statusId !== $training->training_status_id) {
            $training->update(['training_status_id' => $statusId]);
        }

        return (object) [
            'minutesRemaining' => $minutesRemaining,
            'totalMinutes' => $totalMinutes,
        ];
    }

    public static function validateSetRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "id"                    => "nullable|integer|exists:training,id",
            "training_category_id"  => "required|integer|exists:training_category,id",
            "name"                  => "required|string",
            "num_max_absences"      => "required|integer|min:0",
            "start_date"            => "required|date",
            "end_date"              => "required|date|after:start_date",
            "min_participants"      => "required|integer|min:0",
            "max_participants"      => "required|integer|min:0",
            "short_description"     => "required|string",
            "long_description"      => "required|string",
        ]);

        if ($validator->fails()) {
            throw new Exception($validator->errors()->first());
        }
    }

    public static function validateUpdateImageRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'training_id'   => 'required|integer|exists:training,id',
            'file'          => 'required|file|mimes:jpg,jpeg,png,gif',
        ]);

        if ($validator->fails()) {
            throw new Exception($validator->errors()->first());
        }
    }

    public static function validateCreatePersonRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "type"              => "required|string|in:student,teacher",
            "document_type"     => "required|string|exists:person,document_type",
            "document_number"   => "required|string|size:8|unique:person,document_number",
            "names"             => "required|string",
            "phone"             => "required|string|size:9",
            "email"             => "required|email|unique:user,email",
        ]);

        if ($validator->fails()) {
            throw new Exception($validator->errors()->first());
        }
    }

    public static function validateAssignPersonRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'training_id'   => 'required|integer|exists:training,id',
            'person_id'     => 'required|integer|exists:person,id',
        ]);

        if ($validator->fails()) {
            throw new Exception($validator->errors()->first());
        }
    }

    public static function validateListStudentsRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "page"                  => "required|integer|gt:0",
            "size"                  => "required|integer|gt:0",
            "training_id"           => "required|integer|exists:training,id",
            "search"                => "nullable|string",
            "rol"                   => "nullable|string|in:internal,external",
            "status"                => "nullable|string|in:active,suspended,retired",
        ]);

        if ($validator->fails()) {
            throw new Exception($validator->errors()->first());
        }
    }

    public static function validateListStudentsDownloadRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "training_id"           => "required|integer|exists:training,id",
            "search"                => "nullable|string",
            "rol"                   => "nullable|string|in:internal,external",
            "status"                => "nullable|string|in:active,suspended,retired",
        ]);

        if ($validator->fails()) {
            throw new Exception($validator->errors()->first());
        }
    }

    public static function validateActivateStudentRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'training_id'   => 'required|integer|exists:training,id',
            'person_id'     => 'required|integer|exists:person,id',
            'justification' => 'required|string',
        ]);

        if ($validator->fails()) {
            throw new Exception($validator->errors()->first());
        }
    }

    public static function validateCertificateDownloadRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'training_id'   => 'required|integer|exists:training,id',
            'person_id'     => 'required|integer|exists:person,id',
        ]);

        if ($validator->fails()) {
            throw new Exception($validator->errors()->first());
        }
    }

    public static function validateAccess($person_id, $training_id)
    {
        $is_teacher = TrainingTeacher::select()
            ->where('person_id', $person_id)
            ->where('training_id', $training_id)
            ->exists();

        $is_student = TrainingParticipant::select()
            ->where('person_id', $person_id)
            ->where('training_id', $training_id)
            ->where('is_active', true)
            ->exists();

        if (!$is_teacher && !$is_student) {
            throw new Exception("No formas parte de esta clase");
        }
    }

    public static function validateASTAccess($person_id, $rol_id, $training_id)
    {
        $is_secretary = in_array($rol_id, [RolTenant::TRAINING_ADMINISTRADOR]);

        $is_teacher = TrainingTeacher::select()
            ->where('person_id', $person_id)
            ->where('training_id', $training_id)
            ->exists();

        if (!$is_secretary && !$is_teacher) {
            throw new Exception("No tienes permisos para realizar esta acción");
        }
    }

    public static function validateTeacherAccess($person_id, $training_id)
    {
        $teacher = TrainingTeacher::select()
            ->where('person_id', $person_id)
            ->where('training_id', $training_id)
            ->first();

        if (!$teacher) {
            throw new Exception("No eres profesor de esta capacitación");
        }

        return $teacher;
    }

    public static function validateStudentAccess($person_id, $training_id, $isStudent = true)
    {
        $participant = TrainingParticipant::select()
            ->where('person_id', $person_id)
            ->where('training_id', $training_id)
            ->where('is_active', true)
            ->first();

        if (!$participant) {
            throw new Exception($isStudent ? "No eres estudiante de esta capacitación" : "El estudiante no pertenece a esta capacitación");
        }

        return $participant;
    }

    public static function validateASTSAccess($person_id, $rol_id, $training_id)
    {
        $is_secretary = in_array($rol_id, [RolTenant::TRAINING_ADMINISTRADOR]);

        $is_teacher = TrainingTeacher::select()
            ->where('person_id', $person_id)
            ->where('training_id', $training_id)
            ->exists();

        $is_student = TrainingParticipant::select()
            ->where('person_id', $person_id)
            ->where('training_id', $training_id)
            ->where('is_active', true)
            ->exists();

        if (!$is_secretary && !$is_teacher && !$is_student) {
            throw new Exception("No formas parte de esta clase");
        }
    }

    public static function validateReportRequest($request)
    {
        $validator = Validator::make($request->all(), [
            "training_id"  => "required|numeric|exists:training,id",
        ]);

        if ($validator->fails()) {
            throw new Exception($validator->errors()->first());
        }
    }

    public static function checkTrainingStatus(User $user, Training $training, $isRequired = false)
    {
        $isStudent = $user->rol_id === RolTenant::TRAINING_STUDENT;
        $isTrainingNotStarted = $training->training_status_id === TrainingStatusEnum::NOT_STARTED;
        $isTrainingCompleted = $training->training_status_id === TrainingStatusEnum::COMPLETED;

        if ($isStudent || $isRequired) {
            if ($isTrainingNotStarted) {
                throw new Exception('¡La capacitación no ha iniciado!');
            }

            if ($isTrainingCompleted) {
                throw new Exception('¡La capacitación ya ha finalizado!');
            }
        }
    }

    public static function canUpdateDates(Training $training, Request $request)
    {
        $status = $training->training_status_id;

        $rules = [
            'start_date' => [
                'nullable',
                'date',
                'after_or_equal:' . $training->start_date,
                'before_or_equal:' . ($request->end_date ?? $training->end_date),
            ],
            'end_date' => [
                'nullable',
                'date',
                'after_or_equal:start_date',
            ],
        ];

        if ($status === TrainingStatusEnum::IN_PROGRESS) {
            $rules['end_date'] = [
                'nullable',
                'date',
                'after:start_date',
            ];
        } elseif ($status === TrainingStatusEnum::COMPLETED) {
            $rules = [
                'end_date' => [
                    'nullable',
                    'date',
                    'after_or_equal:' . $training->start_date,
                ],
            ];
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            throw new Exception($validator->errors()->first());
        }
    }
}
