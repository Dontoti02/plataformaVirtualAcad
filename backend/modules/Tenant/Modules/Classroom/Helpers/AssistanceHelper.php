<?php

namespace Modules\Tenant\Modules\Classroom\Helpers;

use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Validator;

class AssistanceHelper
{
    public static function validateListRequest($request, $is_teacher)
    {
        $required = $is_teacher ? "required" : "nullable";

        $validator = Validator::make($request->all(), [
            "classroom_id"  => "required|numeric|exists:classroom,id",
            "date"          => "$required|date|date_format:Y-m-d",
        ]);

        if ($validator->fails()) {
            throw new Exception($validator->errors()->first());
        }
    }

    public static function validateRequest($request)
    {
        $now = Carbon::now()->startOfDay();

        $validator = Validator::make($request->all(), [
            "classroom_id"  => "required|numeric|exists:classroom,id",
            "date"          => "required|date|date_format:Y-m-d|before_or_equal:$now",
        ]);

        if ($validator->fails()) {
            throw new Exception($validator->errors()->first());
        }
    }

    public static function validateMarkRequest($request)
    {
        $validator = Validator::make($request->all(), [
            "status"    => "required|string|in:attended,absence,late",
            "reason"    => "nullable|string",
        ]);

        if ($validator->fails()) {
            throw new Exception($validator->errors()->first());
        }
    }

    public static function validateAbsencesPerDayRequest($request)
    {
        $validator = Validator::make($request->all(), [
            "classroom_id"  => "required|numeric|exists:classroom,id",
            "date"          => "required|date|date_format:Y-m-d",
        ]);

        if ($validator->fails()) {
            throw new Exception($validator->errors()->first());
        }
    }

    public static function validateAttendanceConsolidatedRequest($request)
    {
        $validator = Validator::make($request->all(), [
            "classroom_id"  => "required|numeric|exists:classroom,id",
        ]);

        if ($validator->fails()) {
            throw new Exception($validator->errors()->first());
        }
    }
    public static function validateDeleteMultipleRequest($request)
    {
        $validator = Validator::make($request->all(), [
            "classroom_id" => "required|numeric|exists:classroom,id",
            "date" => "required|date|date_format:Y-m-d",
        ]);

        if ($validator->fails()) {
            throw new Exception($validator->errors()->first());
        }
    }
}
