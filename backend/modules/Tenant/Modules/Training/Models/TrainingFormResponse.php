<?php

namespace Modules\Tenant\Modules\Training\Models;

use Illuminate\Database\Eloquent\Model;

class TrainingFormResponse extends Model
{
    protected $table = 'training_form_response';

    protected $fillable = [
        'id',
        'person_id',
        'training_form_id',
        'questions',
        'score',
    ];

    protected $casts = [
        'questions' => 'array',
        'score' => 'float',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];
}
