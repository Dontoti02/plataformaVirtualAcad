<?php

namespace Modules\Tenant\Modules\Classroom\Models;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EvaluationGroup extends Model
{
    protected $table = 'evaluation_group';

    protected $fillable = [
        'classroom_id',
        'title',
        'weight',
    ];

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    public function contents(): HasMany
    {
        return $this->hasMany(Content::class);
    }

    public function averages(): HasMany
    {
        return $this->hasMany(Average::class);
    }

    public function averageDetails(): HasMany
    {
        return $this->hasMany(AverageDetail::class);
    }

    public function scopeByKey($query, $key, $value)
    {
        $result = $query->where($key, $value)->first();

        if (!$result) {
            throw new Exception('Grupo de evaluaciones no encontrado');
        }

        return $result;
    }
}
