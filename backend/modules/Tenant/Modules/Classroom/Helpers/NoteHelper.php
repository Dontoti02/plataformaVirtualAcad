<?php

namespace Modules\Tenant\Modules\Classroom\Helpers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class NoteHelper
{
    public static function validateRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'classroom_id'  => 'nullable|integer|exists:classroom,id',
            'capacity_id'   => 'nullable|integer|exists:capacity,id',
            'indicator_id'  => 'nullable|integer|exists:indicator,id',
        ]);

        if ($validator->fails()) {
            throw new Exception($validator->errors()->first());
        }

        if (!$request->filled('classroom_id') && !$request->filled('capacity_id') && !$request->filled('indicator_id')) {
            throw new Exception('Indica la clase, la capacidad o el indicador.');
        }
    }

    public static function validateReportRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'classroom_id'  => 'required|integer|exists:classroom,id',
            'capacity_id'   => 'nullable|integer|exists:capacity,id',
        ]);

        if ($validator->fails()) {
            throw new Exception($validator->errors()->first());
        }
    }

    public static function validateRecoveryRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'notes'                 => 'required|array|min:1',
            // studentId es la persona: así lo envía la pantalla de la línea actual.
            'notes.*.studentId'     => 'required|integer|exists:person,id',
            'notes.*.capacityId'    => 'required|integer|exists:capacity,id',
            'notes.*.value'         => 'nullable|numeric|min:0|max:20',
        ]);

        if ($validator->fails()) {
            throw new Exception($validator->errors()->first());
        }
    }
}
