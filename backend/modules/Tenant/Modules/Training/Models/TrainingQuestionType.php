<?php

namespace Modules\Tenant\Modules\Training\Models;

use Illuminate\Database\Eloquent\Model;

class TrainingQuestionType extends Model
{
    protected $table = 'training_question_type';

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
