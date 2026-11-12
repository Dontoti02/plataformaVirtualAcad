<?php

namespace Modules\Tenant\Modules\Training\Models;

use Illuminate\Database\Eloquent\Model;

class TrainingStatus extends Model
{
    protected $table = 'training_status';

    protected $fillable = [
        'id',
        'name',
    ];
}
