<?php

namespace Modules\Tenant\Modules\MeritOrder\Helpers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MeritOrderHelper
{
    public static function validateListRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "period_id" => "required|numeric|exists:period,id",
            "study_program_id" => "required|numeric|exists:study_program,id",
            "cycle_id" => "nullable|numeric|exists:cycle,id",
        ]);

        if ($validator->fails()) {
            throw new Exception($validator->errors()->first());
        }
    }
}
