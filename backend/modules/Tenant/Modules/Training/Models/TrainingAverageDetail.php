<?php

namespace Modules\Tenant\Modules\Training\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Tenant\Modules\User\Models\Person;

class TrainingAverageDetail extends Model
{
    protected $table = 'training_average_detail';

    protected $fillable = [
        'person_id',
        'training_content_group_id',
        'training_evaluation_group_id',
        'score',
    ];

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function contentGroup(): BelongsTo
    {
        return $this->belongsTo(TrainingContentGroup::class);
    }

    public function evaluationGroup(): BelongsTo
    {
        return $this->belongsTo(TrainingEvaluationGroup::class);
    }
}
