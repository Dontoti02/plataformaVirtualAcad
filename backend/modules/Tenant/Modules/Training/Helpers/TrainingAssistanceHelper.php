<?php

namespace Modules\Tenant\Modules\Training\Helpers;

use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Validator;
use Modules\Tenant\Modules\Training\Models\Training;

class TrainingAssistanceHelper
{
    public static function validateListRequest($request, $is_teacher)
    {
        $required = $is_teacher ? "required" : "nullable";

        $validator = Validator::make($request->all(), [
            "training_id"   => "required|numeric|exists:training,id",
            "date"          => "$required|date|date_format:Y-m-d",
        ]);

        if ($validator->fails()) {
            throw new Exception($validator->errors()->first());
        }
    }

    public static function validateRequest($request)
    {
        $now = Carbon::now()->startOfDay();

        $trainingId = $request->training_id;

        $training = Training::find($trainingId);

        if (!$training) {
            throw new Exception("Training not found");
        }

        $validator = Validator::make($request->all(), [
            "training_id"   => "required|numeric|exists:training,id",
            "date"          => "required|date|date_format:Y-m-d|after_or_equal:{$training->start_date}|before_or_equal:{$training->end_date}",
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
            "training_id"   => "required|numeric|exists:training,id",
            "date"          => "required|date|date_format:Y-m-d",
        ]);

        if ($validator->fails()) {
            throw new Exception($validator->errors()->first());
        }
    }

    public static function validateAttendanceConsolidatedRequest($request)
    {
        $validator = Validator::make($request->all(), [
            "training_id"   => "required|numeric|exists:training,id",
        ]);

        if ($validator->fails()) {
            throw new Exception($validator->errors()->first());
        }
    }

    public static function validateDeleteMultipleRequest($request)
    {
        $validator = Validator::make($request->all(), [
            "training_id"   => "required|numeric|exists:training,id",
            "date"          => "required|date|date_format:Y-m-d",
        ]);

        if ($validator->fails()) {
            throw new Exception($validator->errors()->first());
        }
    }
}
