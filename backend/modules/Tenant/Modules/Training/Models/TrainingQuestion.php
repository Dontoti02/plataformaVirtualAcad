<?php

namespace Modules\Tenant\Modules\Training\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrainingQuestion extends Model
{
    protected $table = 'training_question';

    protected $fillable = [
        'id',
        'training_form_id',
        'training_question_type_key',
        'key',
        'label',
        'order_number',
        'score_max',
        'options',
    ];

    protected $casts = [
        'score_max' => 'float',
        'options' => 'array',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function form(): BelongsTo
    {
        return $this->belongsTo(TrainingForm::class);
    }
}
