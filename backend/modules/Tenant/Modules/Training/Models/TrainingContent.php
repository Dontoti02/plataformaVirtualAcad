<?php

namespace Modules\Tenant\Modules\Training\Models;

use DateTimeInterface;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Modules\Tenant\Modules\File\Models\File;
use Modules\Tenant\Modules\File\Models\Link;

class TrainingContent extends Model
{
    protected $table = 'training_content';

    protected $fillable = [
        'training_content_group_id',
        'training_evaluation_group_id',
        'title',
        'description',
        'type',
        'is_visible',
        'date_start',
        'date_limit',
        'is_open',
        'score',
        'has_evaluation_form',
        'is_group_task',
    ];

    protected $casts = [
        'is_visible' => 'boolean',
        'is_open' => 'boolean',
        'score' => 'float',
        'has_evaluation_form' => 'boolean',
        'is_group_task' => 'boolean',
    ];

    protected $hidden = [
        'updated_at',
        'deleted_at',
    ];

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    public function contentGroup(): BelongsTo
    {
        return $this->belongsTo(TrainingContentGroup::class, 'training_content_group_id');
    }

    public function evaluationGroup(): BelongsTo
    {
        return $this->belongsTo(TrainingEvaluationGroup::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(TrainingAnswer::class);
    }

    public function comments(): MorphMany
    {
        return $this->morphMany(TrainingComment::class, 'commentable');
    }

    public function files(): MorphMany
    {
        return $this->morphMany(File::class, 'fileable');
    }

    public function links(): MorphMany
    {
        return $this->morphMany(Link::class, 'linkable');
    }

    public function form(): HasOne
    {
        return $this->hasOne(TrainingForm::class);
    }

    public function taskGroups(): HasMany
    {
        return $this->hasMany(TrainingTaskGroup::class, 'training_content_id');
    }

    public function scopeByKey($query, $key, $value)
    {
        $result = $query->where($key, $value)->first();

        if (!$result) {
            throw new Exception('Contenido no encontrado');
        }

        return $result;
    }
}
