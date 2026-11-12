<?php

namespace Modules\Tenant\Modules\Classroom\Models;

use DateTimeInterface;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Modules\Tenant\Modules\EvaluationForm\Models\Form;
use Modules\Tenant\Modules\File\Models\File;
use Modules\Tenant\Modules\File\Models\Link;

class Content extends Model
{
    protected $table = 'content';

    protected $fillable = [
        'content_group_id',
        'evaluation_group_id',
        'indicator_id',
        'title',
        'description',
        'type',
        'is_visible',
        'date_start',
        'date_limit',
        'is_open',
        'score',
        'has_evaluation_form',
    ];

    protected $casts = [
        'is_visible' => 'boolean',
        'is_open' => 'boolean',
        'score' => 'float',
        'has_evaluation_form' => 'boolean',
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
        return $this->belongsTo(ContentGroup::class);
    }

    public function evaluationGroup(): BelongsTo
    {
        return $this->belongsTo(EvaluationGroup::class);
    }

    public function indicator(): BelongsTo
    {
        return $this->belongsTo(Indicator::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(Answer::class);
    }

    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
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
        return $this->hasOne(Form::class);
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
