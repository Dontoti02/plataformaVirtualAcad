<?php

namespace Modules\Tenant\Modules\EvaluationForm\Models;

use Illuminate\Database\Eloquent\Model;

class QuestionType extends Model
{
    protected $table = 'question_type';

    protected $fillable = [
        'id',
        'key',
        'name',
        'data_type',
        'order_number',
        'options'
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];
}
