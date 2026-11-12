<?php

namespace Modules\Tenant\Modules\Management\Helpers;

use Modules\Tenant\Modules\Management\Models\Cycle;

class CycleHelper
{
    public static function list()
    {
        $cycles = Cycle::select()
            ->orderBy('name', 'asc')
            ->get();

        return $cycles;
    }
}
