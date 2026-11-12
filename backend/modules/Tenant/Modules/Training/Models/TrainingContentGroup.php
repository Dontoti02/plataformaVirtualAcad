<?php

namespace Modules\Tenant\Modules\Training\Models;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Modules\Tenant\Modules\File\Models\File;

class TrainingContentGroup extends Model
{
    protected $table = 'training_content_group';

    protected $fillable = [
        'training_id',
        'title',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function training(): BelongsTo
    {
        return $this->belongsTo(Training::class);
    }

    public function contents(): HasMany
    {
        return $this->hasMany(TrainingContent::class);
    }

    public function averageDetails(): HasMany
    {
        return $this->hasMany(TrainingAverageDetail::class);
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
