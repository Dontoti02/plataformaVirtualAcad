<?php

namespace Modules\Tenant\Modules\Classroom\Helpers;

use Exception;
use Illuminate\Support\Facades\Validator;
use Modules\Tenant\Modules\Classroom\Models\EvaluationGroup;

class EvaluationGroupHelper
{
    public static function validateRequest($request)
    {
        $validator = Validator::make($request->all(), [
            "classroom_id"      => "required|numeric|exists:classroom,id",

            "create"            => "nullable|array",
            "create.*.title"    => "required|string|max:255",
            "create.*.weight"   => "required|numeric|between:0.01,1",

            "update"            => "nullable|array",
            "update.*.id"       => "required|numeric|exists:evaluation_group,id",
            "update.*.title"    => "required|string|max:255",
            "update.*.weight"   => "required|numeric|between:0.01,1",

            "delete"            => "nullable|array",
            "delete.*"          => "required|numeric|exists:evaluation_group,id",
        ]);

        if ($validator->fails()) {
            throw new Exception($validator->errors()->first());
        }
    }

    public static function validateWeight($request)
    {
        $create = array_map(function ($item) {
            return $item['weight'];
        }, $request->create);

        $update_ids = array_map(function ($item) {
            return $item['id'];
        }, $request->update);

        $update = array_map(function ($item) {
            return $item['weight'];
        }, $request->update);

        $delete_ids = $request->delete;

        $weights = EvaluationGroup::select()
            ->where('classroom_id', $request->classroom_id)
            ->whereNotIn('id', $update_ids)
            ->whereNotIn('id', $delete_ids)
            ->pluck('weight')
            ->map(function ($item) {
                return floatval($item);
            });

        $total = $weights->sum();
        $total += array_sum($create);
        $total += array_sum($update);

        if ($total != 1) {
            throw new Exception('La suma de los pesos debe ser igual a 1');
        }
    }

    public static function validateTitle(int $classroom_id, string $title, int $id = 0)
    {
        $exists = EvaluationGroup::select()
            ->where('id', '!=', $id)
            ->where('classroom_id', $classroom_id)
            ->where('title', $title)
            ->exists();

        if ($exists) {
            throw new Exception("Ya existe un grupo de evaluación con el mismo nombre ($title)");
        }
    }
}
