<?php

namespace Modules\Admin\Models;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Institution extends Model
{
  protected $connection = 'central';

  protected $table = 'institution';

  protected $fillable = [
    'id',
    'domain_id',
    'modular_code',
    'ruc',
    'name',
    'type_management',
    'department',
    'province',
    'district',
    'address',
    'logo',
    'is_active',
    'start_date',
    'end_date',
  ];

  public function domain(): BelongsTo
  {
    return $this->belongsTo(Domain::class);
  }

  public function storage(): HasOne
  {
    return $this->hasOne(InstitutionStorage::class);
  }

  public function scopeByKey($query, $key, $value)
  {
    $result = $query->where($key, $value)->first();

    if (!$result) {
      throw new Exception('Institución no encontrada');
    }

    return $result;
  }

  public function modules(): HasMany
  {
    return $this->hasMany(InstitutionModule::class, 'institution_id', 'id');
  }
}
