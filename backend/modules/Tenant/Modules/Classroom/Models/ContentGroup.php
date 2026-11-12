<?php

namespace Modules\Tenant\Modules\Classroom\Models;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Modules\Tenant\Modules\File\Models\File;

class ContentGroup extends Model
{
    protected $table = 'content_group';

    protected $fillable = [
        'classroom_id',
        'title',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    public function contents(): HasMany
    {
        return $this->hasMany(Content::class);
    }

    public function averageDetails(): HasMany
    {
        return $this->hasMany(AverageDetail::class);
    }

    public function files(): MorphMany
    {
        return $this->morphMany(File::class, 'fileable');
    }

    public function scopeByKey($query, $key, $value)
    {
        $result = $query->where($key, $value)->first();

        if (!$result) {
            throw new Exception('Grupo de contenido no encontrado');
        }

        return $result;
    }
}
