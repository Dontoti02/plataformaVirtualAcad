<?php

namespace Modules\Tenant\Modules\Training\Models;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Tenant\Modules\User\Models\Person;

class TrainingAssistance extends Model
{
    protected $table = 'training_assistance';

    protected $fillable = [
        'person_id',
        'training_id',
        'status',
        'date',
        'reason',
    ];

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function training(): BelongsTo
    {
        return $this->belongsTo(Training::class);
    }

    public function scopeByKey($query, $key, $value)
    {
        $result = $query->where($key, $value)->first();

        if (!$result) {
            throw new Exception('Asistencia no encontrada');
        }

        return $result;
    }
}
