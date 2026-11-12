<?php

namespace Modules\Tenant\Modules\EvaluationForm\Models;

use Illuminate\Database\Eloquent\Model;

class FormResponse extends Model
{
    protected $table = 'form_response';

    protected $fillable = [
        'id',
        'person_id',
        'form_id',
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
