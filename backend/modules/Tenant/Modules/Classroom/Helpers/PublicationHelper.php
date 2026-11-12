<?php

namespace Modules\Tenant\Modules\Classroom\Helpers;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Modules\Shared\Helpers\SessionManager;
use Illuminate\Support\Facades\Validator;
use Modules\Tenant\Modules\Classroom\Models\Publication;
use Modules\Tenant\Modules\Management\Helpers\PeriodHelper;
use Modules\Tenant\Modules\User\Models\User;

class PublicationHelper
{
    public static function validateListRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "page"          => "required|integer|gt:0",
            "size"          => "required|integer|gt:0",
            "classroom_id"  => "required|numeric|exists:classroom,id",
        ]);

        if ($validator->fails()) {
            throw new Exception($validator->errors()->first());
        }
    }

    public static function validateRequest($request, $exceptId = 0)
    {
        $required = $exceptId ? "nullable" : "required";

        $validator = Validator::make($request->all(), [
            "classroom_id"      => "$required|numeric|exists:classroom,id",
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
        $publication = Publication::find($request->model_id);

        if ($publication->person_id !== $user->person_id) {
            throw new Exception("La publicación no te pertenece");
        }

        $classroom_id = $publication->classroom_id;

        PeriodHelper::validateByClassroom($classroom_id);

        $result = (object) [
            'polymorphic' => $publication,
            'classroom_id' => $classroom_id,
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

        $classroom_id = $publication->classroom_id;

        PeriodHelper::validateByClassroom($classroom_id);
    }
}
