<?php

namespace Modules\Tenant\Modules\JobOpportunities\Shared\Domain\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Tenant\Modules\JobOpportunities\Shared\Domain\Models\JobCompany;

class JobOffer extends Model
{
  use SoftDeletes;
  
  protected $table = 'job_opportunity_offer';

  protected $fillable = [
    'title',
    'slug',
    'description',
    'requirements',
    'publication_date',
    'deadline',
    'benefits',
    'salary',
    'salary_currency',
    'address',
    'department',
    'province',
    'country',
    'attachments',
    'company_id',
    'location_id',
    'state_id',
    'category_id',
    'work_schedule_id',
    'contract_type_id',
  ];
  protected $casts = [
    'publication_date' => 'datetime:d-m-Y H:i:s',
    'deadline' => 'datetime',
    'attachments' => 'array',
  ];
  protected $hidden = [
    'created_at',
    'updated_at',
  ];
  public function company()
  {
    return $this->belongsTo(JobCompany::class);
  }
  public function location()
  {
    return $this->belongsTo(JobLocation::class);
  }
  public function category()
  {
    return $this->belongsTo(JobOfferCategory::class);
  }
  public function schedule()
  {
    return $this->belongsTo(JobWorkSchedule::class, 'work_schedule_id');
  }
  public function contractType()
  {
    return $this->belongsTo(JobContractType::class);
  }
  public function currentState()
  {
    return $this->belongsTo(JobOfferState::class);
  }
  
  public function state() {
    return $this->hasOne(JobOfferState::class, 'id', 'state_id');
  }

  public function states()
  {
    return $this->belongsToMany(JobOfferState::class, 'job_opportunity_offer_state_detail', 'offer_id', 'state_id');
  }
  
  public function applications()
  {
    return $this->hasMany(JobOfferApplications::class,'offer_id');
  }
}
