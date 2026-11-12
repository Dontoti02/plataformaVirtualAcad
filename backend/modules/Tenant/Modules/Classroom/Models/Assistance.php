<?php

namespace Modules\Tenant\Modules\Classroom\Models;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Tenant\Modules\User\Models\Person;

class Assistance extends Model
{
    protected $table = 'assistance';

    protected $fillable = [
        'person_id',
        'classroom_id',
        'status',
        'date',
        'reason',
    ];

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    public function scopeByKey($query, $key, $value)
    {
        $result = $query->where($key, $value)->first();

        if (!$result) {
            throw new Exception('Asistencia no encontrada');
        }

        return $result;
    }
}
