<?php

namespace Modules\Tenant\Modules\JobOpportunities\Shared\Domain\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class JobOfferCategory extends Model
{
   use SoftDeletes;
   
  protected $table = 'job_opportunity_offer_category';

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
    return $this->hasMany(JobOffer::class, 'category_id', 'id');
  }
}
