<?php

namespace Modules\Tenant\Modules\Management\Helpers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RegistrationHelper
{
    public static function validateListStudentsRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "period_id"         => "required|numeric|exists:period,id",
            "study_program_id"  => "required|numeric|exists:study_program,id",
        ]);

        if ($validator->fails()) {
            throw new Exception($validator->errors()->first());
        }
    }

    public static function validateListRequest(Request $request, $is_student)
    {
        $required = $is_student ? "nullable" : "required";

        $validator = Validator::make($request->all(), [
            "period_id" => "required|numeric|exists:period,id",
            "person_id" => "$required|numeric|exists:person,id",
        ]);

        if ($validator->fails()) {
            throw new Exception($validator->errors()->first());
        }
    }

    public static function validateConsolidatedRequest(Request $request, $is_student)
    {
        $required = $is_student ? "nullable" : "required";

        $validator = Validator::make($request->all(), [
            "person_id" => "$required|numeric|exists:person,id",
        ]);

        if ($validator->fails()) {
            throw new Exception($validator->errors()->first());
        }
    }
}
