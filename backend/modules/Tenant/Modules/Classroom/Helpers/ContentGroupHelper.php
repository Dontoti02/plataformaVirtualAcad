<?php

namespace Modules\Tenant\Modules\Classroom\Helpers;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Modules\Shared\Helpers\SessionManager;
use Illuminate\Support\Facades\Validator;
use Modules\Tenant\Modules\Classroom\Models\ContentGroup;
use Modules\Tenant\Modules\Management\Helpers\PeriodHelper;
use Modules\Tenant\Modules\User\Models\User;

class ContentGroupHelper
{
    public static function validateRequest(Request $request, $exceptId = 0)
    {
        $required = $exceptId ? "nullable" : "required";

        $validator = Validator::make($request->all(), [
            "classroom_id"  => "$required|numeric|exists:classroom,id",
            "title"         => "required|string|max:255|unique:content_group,title,$exceptId,id,classroom_id,$request->classroom_id",
        ]);

        if ($validator->fails()) {
            throw new Exception($validator->errors()->first());
        }
    }

    public static function validateUploadFile(Request $request)
    {
        $session = SessionManager::get();

        $user = User::find($session->id);
        $content_group = ContentGroup::find($request->model_id);

        $classroom_id = $content_group->classroom_id;

        ClassroomHelper::validateASTAccess($user->person_id, $user->rol_id, $classroom_id);
        PeriodHelper::validateByClassroom($classroom_id);

        $result = (object) [
            'polymorphic' => $content_group,
            'classroom_id' => $classroom_id,
        ];

        return $result;
    }

    public static function validateDelete(string $model, Model $polymorphic)
    {
        $session = SessionManager::get();

        $user = User::find($session->id);
        $content_group = $model === 'file'
            ? $polymorphic->fileable
            : $polymorphic->linkable;

        $classroom_id = $content_group->classroom_id;

        ClassroomHelper::validateASTAccess($user->person_id, $user->rol_id, $classroom_id);
        PeriodHelper::validateByClassroom($classroom_id);
    }
}
