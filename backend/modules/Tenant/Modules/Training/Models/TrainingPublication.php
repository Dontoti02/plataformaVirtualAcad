<?php

namespace Modules\Tenant\Modules\Training\Models;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Modules\Tenant\Modules\File\Models\File;
use Modules\Tenant\Modules\User\Models\Person;

class TrainingPublication extends Model
{
    protected $table = 'training_publication';

    protected $fillable = [
        'person_id',
        'training_id',
        'value',
    ];

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function training(): BelongsTo
    {
        return $this->belongsTo(Training::class);
    }

    public function comments(): MorphMany
    {
        return $this->morphMany(TrainingComment::class, 'commentable');
    }

    public function files(): MorphMany
    {
        return $this->morphMany(File::class, 'fileable');
    }

    public function scopeByKey($query, $key, $value)
    {
        $result = $query->where($key, $value)->first();

        if (!$result) {
            throw new Exception('Publicación no encontrada');
        }

        return $result;
    }
}
