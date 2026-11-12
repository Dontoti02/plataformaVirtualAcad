<?php

namespace Modules\Tenant\Modules\Management\Helpers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Modules\Tenant\Modules\Management\Models\Period;

class PeriodHelper
{
    public static function list()
    {
        $periods = Period::select()
            ->orderBy('name', 'desc')
            ->get();

        return $periods;
    }

    public static function current()
    {
        $period = Period::select()
            ->where('status', 'SIN TERMINO')
            ->orderBy('name', 'desc')
            ->first();

        if (!$period) {
            $period = Period::select()
                ->orderBy('name', 'desc')
                ->first();
        }

        return $period;
    }

    public static function validateByClassroom(int $classroom_id)
    {
        $period = Period::select()
            ->join('classroom', 'period.id', 'classroom.period_id')
            ->where('classroom.id', $classroom_id)
            ->where('period.status', 'SIN TERMINO')
            ->exists();

        if (!$period) {
            throw new Exception('El periodo de la clase ha finalizado');
        }
    }

    public static function validateByTraining(int $training_id)
    {
        $period = Period::select()
            ->join('training', 'period.id', 'training.period_id')
            ->where('training.id', $training_id)
            ->where('period.status', 'SIN TERMINO')
            ->exists();

        if (!$period) {
            throw new Exception('El periodo de la capacitación ha finalizado');
        }
    }

    public static function validateListClassroomsRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "page"              => "required|integer|gt:0",
            "size"              => "required|integer|gt:0",
            "period_id"         => "required|numeric|exists:period,id",
            "study_program_id"  => "nullable|numeric|exists:study_program,id",
            "search"            => "nullable|string",
        ]);

        if ($validator->fails()) {
            throw new Exception($validator->errors()->first());
        }
    }
}
