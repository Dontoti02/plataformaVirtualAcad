<?php

namespace Modules\Tenant\Modules\File\Helpers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LinkHelper
{
    public static function validateRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "model"     => "required|string|in:content,answer,publication,training_content,training_answer,training_publication",
            "model_id"  => "required|numeric|exists:$request->model,id",

            "link"      => "required|string",
        ]);

        if ($validator->fails()) {
            throw new Exception($validator->errors()->first());
        }
    }
}
