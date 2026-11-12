<?php

namespace Modules\Tenant\Modules\Training\Models;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TrainingForm extends Model
{
    protected $table = 'training_form';

    protected $fillable = [
        'id',
        'training_content_id',
        'uuid',
        'title',
        'description',
        'score_max',
    ];

    protected $casts = [
        'score_max' => 'float',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function content(): BelongsTo
    {
        return $this->belongsTo(TrainingContent::class,'training_content_id');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(TrainingQuestion::class);
    }

    public function responses(): HasMany
    {
        return $this->hasMany(TrainingFormResponse::class);
    }

    public function scopeByKey($query, $key, $value)
    {
        $result = $query->where($key, $value)->first();

        if (!$result) {
            throw new Exception('Formulario de evaluación no encontrado');
        }

        return $result;
    }
}
