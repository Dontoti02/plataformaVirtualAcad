<?php

namespace Modules\Tenant\Modules\Classroom\Helpers;

use Exception;
use Illuminate\Support\Facades\Validator;

class CommentHelper
{
    public static function validateRequest($request, $exceptId = 0)
    {
        $required = $exceptId ? "nullable" : "required";

        $validator = Validator::make($request->all(), [
            "model"     => "$required|string|in:content,publication",
            "model_id"  => "$required|numeric|exists:$request->model,id",
            "comment"   => "required|string",
        ]);

        if ($validator->fails()) {
            throw new Exception($validator->errors()->first());
        }
    }
}
