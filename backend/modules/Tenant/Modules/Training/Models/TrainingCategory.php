<?php

namespace Modules\Tenant\Modules\Training\Models;

use Illuminate\Database\Eloquent\Model;

class TrainingCategory extends Model
{
  protected $table = 'training_category';

  protected $fillable = [
    'id',
    'name',
  ];

  public $timestamps = false;
}
