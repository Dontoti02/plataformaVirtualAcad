<?php

namespace Modules\Tenant\Modules\Classroom\Models;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Capacidad de una clase: reemplaza a la competencia (evaluation_group). La
 * nota final del estudiante es el promedio de sus capacidades.
 */
class Capacity extends Model
{
    protected $table = 'capacity';

    protected $fillable = [
        'classroom_id',
        'evaluation_group_id',
        'name',
        'order',
        'weight',
    ];

    protected $hidden = [
        'evaluation_group_id',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $casts = [
        'order' => 'integer',
        'weight' => 'float',
    ];

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    public function indicators(): HasMany
    {
        return $this->hasMany(Indicator::class);
    }

    public function studentCapacities(): HasMany
    {
        return $this->hasMany(StudentCapacity::class);
    }

    public function scopeByKey($query, $key, $value)
    {
        $result = $query->where($key, $value)->first();

        if (!$result) {
            throw new Exception('Capacidad no encontrada');
        }

        return $result;
    }
}
