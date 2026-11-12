<?php

namespace Modules\Tenant\Modules\Classroom\Helpers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class EvaluationHelper
{
    public static function validateRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "answer_id"     => "required|numeric|exists:answer,id",
            "score"         => "required|numeric|between:0,20",
        ]);

        if ($validator->fails()) {
            throw new Exception($validator->errors()->first());
        }
    }

    public static function validateFinalNotesRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "classroom_id"     => "required|numeric|exists:classroom,id",
        ]);

        if ($validator->fails()) {
            throw new Exception($validator->errors()->first());
        }
    }
}
