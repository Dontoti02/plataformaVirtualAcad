<?php

namespace Modules\Tenant\Modules\Schedule\Models;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Tenant\Modules\Classroom\Models\Classroom;

class Schedule extends Model
{
    protected $table = 'schedule';

    protected $fillable = [
        'classroom_id',
        'day',
        'hour_start',
        'hour_end',
    ];

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    public function scopeByKey($query, $key, $value)
    {
        $result = $query->where($key, $value)->first();

        if (!$result) {
            throw new Exception('Horario no encontrado');
        }

        return $result;
    }
}
