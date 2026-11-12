<?php

namespace Modules\Tenant\Modules\JobOpportunities\Shared\Domain\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class JobCompany extends Model
{
   use SoftDeletes;
   
  protected $table = 'job_opportunity_company';

  protected $fillable = [
    'name',
    'email',
    'phone',
    'ruc',
    'mailbox',
    'description',
    'website',
    'address',
    'is_verified',
    'logo'
  ];

  public function offers()
  {
    return $this->hasMany(JobOffer::class, 'company_id', 'id');
  }
}
