<?php

namespace Modules\Tenant\Modules\Training\Models;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Tenant\Modules\User\Models\Person;

class TrainingParticipant extends Model
{
    protected $table = 'training_participant';

    protected $fillable = [
        'person_id',
        'training_id',
        'score',
        'is_favorite',
        'is_active', // true (activo), false (suspendido) y null (retirado)
        'justification',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $casts = [
        'is_favorite' => 'boolean',
        'is_active' => 'boolean',
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
            throw new Exception('Participante no encontrado');
        }

        return $result;
    }
}
