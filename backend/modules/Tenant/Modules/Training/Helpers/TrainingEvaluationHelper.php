<?php

namespace Modules\Tenant\Modules\Training\Helpers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TrainingEvaluationHelper
{
    public static function validateRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "training_answer_id"    => "required|numeric|exists:training_answer,id",
            "score"                 => "required|numeric|between:0,20",
        ]);

        if ($validator->fails()) {
            throw new Exception($validator->errors()->first());
        }
    }

    public static function validateFinalNotesRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "training_id"     => "required|numeric|exists:training,id",
        ]);

        if ($validator->fails()) {
            throw new Exception($validator->errors()->first());
        }
    }
}
