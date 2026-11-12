<?php

namespace Modules\Tenant\Modules\Treasury\Adapters;

use Modules\Tenant\Modules\Enroll\Models\Scale;

class ScalesAdapter
{
    public static function transform(Scale $scale)
    {
        return [
            'id' => $scale->id,
            'name' => $scale->name,
            'scale_amount' => $scale->scale_amount,
        ];
    }
}