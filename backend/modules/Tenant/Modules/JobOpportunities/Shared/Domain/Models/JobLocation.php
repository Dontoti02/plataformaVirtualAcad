<?php

namespace Modules\Tenant\Modules\JobOpportunities\Shared\Domain\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class JobLocation extends Model
{
   use SoftDeletes;
   
  protected $table = 'job_opportunity_location';
  protected $fillable = [
    'name',
    'address',
    'city',
    'created_at',
    'updated_at'
  ];
  protected $hidden = [
    'created_at',
    'updated_at'
  ];

  public function offers()
  {
    return $this->hasMany(JobOffer::class, 'location_id', 'id');
  }
}
