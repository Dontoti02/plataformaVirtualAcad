<?php

namespace Modules\Tenant\Modules\Classroom\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Nota de una persona en un indicador. Por persona: ver la migración. */
class StudentIndicator extends Model
{
    protected $table = 'student_indicator';

    protected $fillable = [
        'person_id',
        'indicator_id',
        'score',
    ];

    protected $casts = [
        'score' => 'float',
    ];

    public function indicator(): BelongsTo
    {
        return $this->belongsTo(Indicator::class);
    }
}
