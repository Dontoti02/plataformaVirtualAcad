<?php

namespace Modules\Tenant\Modules\JobOpportunities\Shared\Domain\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class JobOfferStateDetail extends Model
{
   use SoftDeletes;
  protected $table = 'job_opportunity_offer_state_detail';

  protected $fillable = [
    'offer_id',
    'state_id',
    'created_at',
    'updated_at'
  ];

  protected $hidden = [
    'created_at',
    'updated_at'
  ];
}
