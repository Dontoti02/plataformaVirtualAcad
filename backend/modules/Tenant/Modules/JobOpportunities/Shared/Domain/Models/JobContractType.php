<?php

namespace Modules\Tenant\Modules\JobOpportunities\Shared\Domain\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class JobContractType extends Model
{
   use SoftDeletes;
   
  protected $table = 'job_opportunity_contract_types';
  protected $fillable = [
    'name',
    'description',
    'created_at',
    'updated_at'
  ];
  protected $hidden = [
    'created_at',
    'updated_at'
  ];

  public function offers()
  {
    return $this->hasMany(JobOffer::class, 'contract_type_id', 'id');
  }
}
