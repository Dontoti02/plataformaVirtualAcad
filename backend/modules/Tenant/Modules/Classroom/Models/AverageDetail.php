<?php

namespace Modules\Tenant\Modules\Classroom\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Tenant\Modules\User\Models\Person;

class AverageDetail extends Model
{
    protected $table = 'average_detail';

    protected $fillable = [
        'person_id',
        'content_group_id',
        'evaluation_group_id',
        'score',
    ];

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function contentGroup(): BelongsTo
    {
        return $this->belongsTo(ContentGroup::class);
    }

    public function evaluationGroup(): BelongsTo
    {
        return $this->belongsTo(EvaluationGroup::class);
    }
}
