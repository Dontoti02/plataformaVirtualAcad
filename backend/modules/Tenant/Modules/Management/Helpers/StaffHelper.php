<?php

namespace Modules\Tenant\Modules\Management\Helpers;

use Illuminate\Support\Facades\Validator;

class StaffHelper
{
    public static function validateRequest($request)
    {
        $validator = Validator::make($request->all(), [
            "page"          => "required|numeric",
            "size"          => "required|numeric",
            "period_id"     => "required|numeric|exists:period,id",
            "type"          => "nullable|string|exists:staff,type",
            "search"        => "nullable|string",
        ]);

        if ($validator->fails()) {
            throw new \Exception($validator->errors()->first());
        }
    }
}
