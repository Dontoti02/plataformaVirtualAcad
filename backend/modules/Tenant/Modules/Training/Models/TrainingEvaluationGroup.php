<?php

namespace Modules\Tenant\Modules\Training\Models;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TrainingEvaluationGroup extends Model
{
    protected $table = 'training_evaluation_group';

    protected $fillable = [
        'training_id',
        'title',
        'weight',
    ];

    public function training(): BelongsTo
    {
        return $this->belongsTo(Training::class);
    }

    public function contents(): HasMany
    {
        return $this->hasMany(TrainingContent::class);
    }

    public function averages(): HasMany
    {
        return $this->hasMany(TrainingAverage::class);
    }

    public function averageDetails(): HasMany
    {
        return $this->hasMany(TrainingAverageDetail::class);
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
