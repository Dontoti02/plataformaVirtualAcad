<?php

namespace Modules\Tenant\Modules\Training\Models;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\Tenant\Modules\Management\Models\Period;

class Training extends Model
{
    protected $table = 'training';

    protected $fillable = [
        'period_id',
        'training_category_id',
        'training_status_id',
        'name',
        'image',
        'num_max_absences',
        'start_date',
        'end_date',
        'min_participants',
        'max_participants',
        'short_description',
        'long_description',
        'is_group_task'
    ];
    protected $casts = [
        'start_date' => 'date:d-m-Y',
        'end_date' => 'date:d-m-Y',
    ];

    public function period(): BelongsTo
    {
        return $this->belongsTo(Period::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(TrainingCategory::class);
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(TrainingStatus::class);
    }

    public function teacher(): HasOne
    {
        return $this->hasOne(TrainingTeacher::class);
    }

    public function participants(): HasMany
    {
        return $this->hasMany(TrainingParticipant::class);
    }

    public function assistances(): HasMany
    {
        return $this->hasMany(TrainingAssistance::class);
    }

    public function contentGroups(): HasMany
    {
        return $this->hasMany(TrainingContentGroup::class);
    }

    public function evaluationGroups(): HasMany
    {
        return $this->hasMany(TrainingEvaluationGroup::class);
    }

    public function publications(): HasMany
    {
        return $this->hasMany(TrainingPublication::class);
    }

    public function taskGroups(): HasMany
    {
        return $this->hasMany(TrainingTaskGroup::class, 'training_id');
    }

    public function scopeByKey($query, $key, $value)
    {
        $result = $query->where($key, $value)->first();

        if (!$result) {
            throw new Exception('Capacitación no encontrada');
        }

        return $result;
    }
}
