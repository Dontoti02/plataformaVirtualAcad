<?php

namespace Modules\Tenant\Modules\EvaluationForm\Models;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Tenant\Modules\Classroom\Models\Content;

class Form extends Model
{
    protected $table = 'form';

    protected $fillable = [
        'id',
        'content_id',
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
        return $this->belongsTo(Content::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class);
    }

    public function responses(): HasMany
    {
        return $this->hasMany(FormResponse::class);
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
