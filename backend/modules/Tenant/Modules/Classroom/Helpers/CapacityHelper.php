<?php

namespace Modules\Tenant\Modules\Classroom\Helpers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CapacityHelper
{
    public static function validateCreateOrUpdateRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'classroom_id'  => 'required|integer|exists:classroom,id',
            'name'          => 'required|string',
        ]);

        if ($validator->fails()) {
            throw new Exception($validator->errors()->first());
        }
    }

    public static function validateCreateOrUpdateIndicatorRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'capacity_id'   => 'required|integer|exists:capacity,id',
            'name'          => 'required|string',
        ]);

        if ($validator->fails()) {
            throw new Exception($validator->errors()->first());
        }
    }

    public static function validateSaveRequest(Request $request, bool $isUpdate = false)
    {
        $rules = [
            'classroom_id'          => 'required|integer|exists:classroom,id',
            'name'                  => 'required|string',
            'indicators'            => 'required|array|min:1',
            'indicators.*.name'     => 'required|string',
            'indicators.*.order'    => 'nullable|integer|min:1',
        ];

        if ($isUpdate) {
            $rules['indicators.*.id'] = 'nullable|integer|exists:indicator,id';
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            throw new Exception($validator->errors()->first());
        }

        $names = array_map(
            fn ($indicator) => mb_strtolower(trim($indicator['name'])),
            $request->input('indicators')
        );

        if (count($names) !== count(array_unique($names))) {
            throw new Exception('No se pueden repetir los nombres de los indicadores.');
        }
    }
}
