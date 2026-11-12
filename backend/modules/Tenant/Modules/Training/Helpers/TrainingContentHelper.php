<?php

namespace Modules\Tenant\Modules\Training\Helpers;

use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Modules\Shared\Helpers\SessionManager;
use Illuminate\Support\Facades\Validator;
use Modules\Tenant\Modules\Management\Helpers\PeriodHelper;
use Modules\Tenant\Modules\Training\Models\TrainingContent;
use Modules\Tenant\Modules\User\Models\User;

class TrainingContentHelper
{
    public static function validateRequest(Request $request, $exceptId = 0)
    {
        $validator = Validator::make($request->all(), [
            "training_content_group_id"     => "required|numeric|exists:training_content_group,id",
            "training_evaluation_group_id"  => "required_if:type,task,evaluation|numeric|exists:training_evaluation_group,id",
            "title"                         => "required|string|max:255|unique:training_content,title,$exceptId,id,training_content_group_id,$request->training_content_group_id",
            "description"                   => "nullable|string",
            "type"                          => "required|string|in:content,task,evaluation",
            "date_start"                    => "required_if:type,task,evaluation|date",
            "date_limit"                    => "required_if:type,task,evaluation|date",
            "score"                         => "required_if:type,task,evaluation|numeric|between:0,20",
            "has_evaluation_form"           => "required_if:type,evaluation|boolean",
            "form"                          => "required_if:has_evaluation_form,true|array",
            "is_group_task"                 => "required_if:type,task|boolean",
            "groups"                        => "nullable|array",
            "groups.*.name"                 => "required_with:groups|string|max:255",
            "groups.*.participants"         => "required_with:groups|array",
            "groups.*.participants.*"       => "required_with:groups|integer|exists:training_participant,id",
        ]);

        if ($validator->fails()) {
            throw new Exception($validator->errors()->first());
        }
    }

    public static function validateDateStartAndLimit(Request $request, $dateStart = null)
    {
        if ($request->filled('date_start') && $request->filled('date_limit')) {

            $date = Carbon::now()->startOfDay();
            $date_start = Carbon::parse($request->date_start)->startOfDay();
            $date_limit = Carbon::parse($request->date_limit);

            $dateStart = $dateStart ? Carbon::parse($dateStart)->startOfDay() : null;

            if (!$dateStart || $dateStart != $date_start) {
                if ($date_start < $date) {
                    throw new Exception("La fecha de inicio debe ser mayor o igual a la fecha actual.");
                }
            }

            if ($date_limit <= Carbon::now()) {
                throw new Exception("La fecha límite debe ser mayor a la fecha actual.");
            }
        }
    }

    public static function validateUploadFile(Request $request)
    {
        $session = SessionManager::get();

        $user = User::find($session->id);

        $content = TrainingContent::find($request->model_id);

        $training_id = $content->contentGroup->training_id;

        TrainingHelper::validateTeacherAccess($user->person_id, $training_id);
        PeriodHelper::validateByTraining($training_id);

        $result = (object) [
            'polymorphic' => $content,
            'training_id' => $training_id,
        ];

        return $result;
    }

    public static function validateDelete(string $model, Model $polymorphic)
    {
        $session = SessionManager::get();

        $user = User::find($session->id);
        $content = $model === 'file'
            ? $polymorphic->fileable
            : $polymorphic->linkable;

        $training_id = $content->contentGroup->training_id;

        TrainingHelper::validateTeacherAccess($user->person_id, $training_id);
        PeriodHelper::validateByTraining($training_id);
    }
}
