<?php

namespace Modules\Tenant\Modules\Training\Helpers;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Modules\Shared\Helpers\SessionManager;
use Illuminate\Support\Facades\Validator;
use Modules\Tenant\Modules\Management\Helpers\PeriodHelper;
use Modules\Tenant\Modules\Training\Models\TrainingPublication;
use Modules\Tenant\Modules\User\Models\User;

class TrainingPublicationHelper
{
    public static function validateListRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "page"          => "required|integer|gt:0",
            "size"          => "required|integer|gt:0",
            "training_id"   => "required|numeric|exists:training,id",
        ]);

        if ($validator->fails()) {
            throw new Exception($validator->errors()->first());
        }
    }

    public static function validateRequest($request, $exceptId = 0)
    {
        $required = $exceptId ? "nullable" : "required";

        $validator = Validator::make($request->all(), [
            "training_id"       => "$required|numeric|exists:training,id",
            "value"             => "required|string",
        ]);

        if ($validator->fails()) {
            throw new Exception($validator->errors()->first());
        }
    }

    public static function validateUploadFile(Request $request)
    {
        $session = SessionManager::get();

        $user = User::find($session->id);
        $publication = TrainingPublication::find($request->model_id);

        if ($publication->person_id !== $user->person_id) {
            throw new Exception("La publicación no te pertenece");
        }

        $training_id = $publication->training_id;

        PeriodHelper::validateByTraining($training_id);

        $result = (object) [
            'polymorphic' => $publication,
            'training_id' => $training_id,
        ];

        return $result;
    }

    public static function validateDelete(string $model, Model $polymorphic)
    {
        $session = SessionManager::get();

        $user = User::find($session->id);
        $publication = $model === 'file'
            ? $polymorphic->fileable
            : $polymorphic->linkable;

        if ($publication->person_id !== $user->person_id) {
            throw new Exception("La publicación no te pertenece");
        }

        $training_id = $publication->training_id;

        PeriodHelper::validateByTraining($training_id);
    }
}
