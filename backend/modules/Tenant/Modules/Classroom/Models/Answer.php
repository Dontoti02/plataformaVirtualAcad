<?php

namespace Modules\Tenant\Modules\Classroom\Models;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Modules\Tenant\Modules\File\Models\File;
use Modules\Tenant\Modules\File\Models\Link;
use Modules\Tenant\Modules\User\Models\Person;

class Answer extends Model
{
    protected $table = 'answer';

    protected $fillable = [
        'person_id',
        'content_id',
        'status',
        'score',
    ];

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function content(): BelongsTo
    {
        return $this->belongsTo(Content::class);
    }

    public function files(): MorphMany
    {
        return $this->morphMany(File::class, 'fileable');
    }

    public function links(): MorphMany
    {
        return $this->morphMany(Link::class, 'linkable');
    }

    public function scopeByKey($query, $key, $value)
    {
        $result = $query->where($key, $value)->first();

        if (!$result) {
            throw new Exception('Respuesta no encontrada');
        }

        return $result;
    }
}
