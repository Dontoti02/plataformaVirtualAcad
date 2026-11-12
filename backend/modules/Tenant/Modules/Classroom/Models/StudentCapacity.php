<?php

namespace Modules\Tenant\Modules\Classroom\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Nota de una persona en una capacidad. recover_score, si existe, sustituye a
 * score en la nota final.
 */
class StudentCapacity extends Model
{
    protected $table = 'student_capacity';

    protected $fillable = [
        'person_id',
        'capacity_id',
        'score',
        'score_by_weight',
        'recover_score',
    ];

    protected $casts = [
        'score' => 'float',
        'score_by_weight' => 'float',
        'recover_score' => 'float',
    ];

    public function capacity(): BelongsTo
    {
        return $this->belongsTo(Capacity::class);
    }
}
