<?php

namespace Modules\Tenant\Modules\Treasury\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MovementType extends Model
{

  const INCOME = 1;
  const EXPENSE = 2;

  protected $table = 'treasury_movement_type';

  protected $fillable = [
    'code',
    'name',
  ];

  protected $dates = [
    'created_at',
    'updated_at',
    'deleted_at',
  ];

  protected $primaryKey = 'id';

  public $incrementing = true;


  protected $casts = [
    'created_at' => 'datetime',
    'updated_at' => 'datetime',
    'deleted_at' => 'datetime',
  ];

  public function movements()
  {
    return $this->hasMany(Movement::class);
  }
  use SoftDeletes;
}
