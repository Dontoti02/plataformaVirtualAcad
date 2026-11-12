<?php

namespace Modules\Tenant\Modules\Training\Helpers;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Modules\Shared\Helpers\SessionManager;
use Illuminate\Support\Facades\Validator;
use Modules\Tenant\Modules\Management\Helpers\PeriodHelper;
use Modules\Tenant\Modules\Training\Models\TrainingContentGroup;
use Modules\Tenant\Modules\User\Models\User;

class TrainingContentGroupHelper
{
    public static function validateRequest(Request $request, $exceptId = 0)
    {
        $required = $exceptId ? "nullable" : "required";

        $validator = Validator::make($request->all(), [
            "training_id"   => "$required|numeric|exists:training,id",
            "title"         => "required|string|max:255|unique:training_content_group,title,$exceptId,id,training_id,$request->training_id",
        ]);

        if ($validator->fails()) {
            throw new Exception($validator->errors()->first());
        }
    }

    public static function validateUploadFile(Request $request)
    {
        $session = SessionManager::get();

        $user = User::find($session->id);
        $training_content_group = TrainingContentGroup::find($request->model_id);

        $training_id = $training_content_group->training_id;

        TrainingHelper::validateASTAccess($user->person_id, $user->rol_id, $training_id);
        PeriodHelper::validateByTraining($training_id);

        $result = (object) [
            'polymorphic' => $training_content_group,
            'training_id' => $training_id,
        ];

        return $result;
    }

    public static function validateDelete(string $model, Model $polymorphic)
    {
        $session = SessionManager::get();

        $user = User::find($session->id);
        $training_content_group = $model === 'file'
            ? $polymorphic->fileable
            : $polymorphic->linkable;

        $training_id = $training_content_group->training_id;

        TrainingHelper::validateASTAccess($user->person_id, $user->rol_id, $training_id);
        PeriodHelper::validateByTraining($training_id);
    }
}
