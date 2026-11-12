<?php

namespace Modules\Tenant\Modules\Classroom\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Tenant\Modules\User\Models\Person;

class Average extends Model
{
    protected $table = 'average';

    protected $fillable = [
        'person_id',
        'evaluation_group_id',
        'score',
    ];

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function evaluationGroup(): BelongsTo
    {
        return $this->belongsTo(EvaluationGroup::class);
    }
}
