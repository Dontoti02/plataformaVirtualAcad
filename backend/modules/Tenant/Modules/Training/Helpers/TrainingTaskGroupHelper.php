<?php

namespace Modules\Tenant\Modules\Training\Helpers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TrainingTaskGroupHelper
{
    public static function validateSetRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id'                    => 'nullable|integer|exists:training_task_group,id',
            'training_content_id'   => 'required|integer|exists:training_content,id',
            'name'                  => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            throw new Exception($validator->errors()->first());
        }
    }

    public static function validateSetParticipantRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'training_task_group_id'    => 'required|integer|exists:training_task_group,id',
            'training_participant_id'   => 'required|integer|exists:training_participant,id',
        ]);

        if ($validator->fails()) {
            throw new Exception($validator->errors()->first());
        }
    }
}
