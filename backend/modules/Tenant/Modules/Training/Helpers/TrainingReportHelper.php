<?php

namespace Modules\Tenant\Modules\Training\Helpers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TrainingReportHelper
{
    public static function validateListRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "page"                  => "required|integer|gt:0",
            "size"                  => "required|integer|gt:0",
            "search"                => "nullable|string",
            "training_status_id"    => "nullable|integer|exists:training_status,id",
        ]);

        if ($validator->fails()) {
            throw new Exception($validator->errors()->first());
        }
    }

    public static function validateListDownloadRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "search"                => "nullable|string",
            "training_status_id"    => "nullable|integer|exists:training_status,id",
        ]);

        if ($validator->fails()) {
            throw new Exception($validator->errors()->first());
        }
    }
}
