<?php

namespace Modules\Tenant\Modules\JobOpportunities\Shared\Domain\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class JobWorkSchedule extends Model
{
   use SoftDeletes;
  protected $table = 'job_opportunity_work_schedules';

  protected $fillable = [
    'name',
    'created_at',
    'updated_at'
  ];

  protected $hidden = [
    'created_at',
    'updated_at'
  ];

  public function offers()
  {
    return $this->hasMany(JobOffer::class, 'work_schedule_id', 'id');
  }
}
