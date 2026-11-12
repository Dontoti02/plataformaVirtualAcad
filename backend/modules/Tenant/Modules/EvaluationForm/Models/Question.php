<?php

namespace Modules\Tenant\Modules\EvaluationForm\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Question extends Model
{
    protected $table = 'question';

    protected $fillable = [
        'id',
        'form_id',
        'question_type_key',
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
        return $this->belongsTo(Form::class);
    }
}
