<?php

namespace Modules\Tenant\Modules\JobOpportunities\Shared\Domain\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Tenant\Modules\User\Models\User;

class JobOfferCv extends Model
{
  use SoftDeletes;
  
  protected $table = 'job_opportunity_user_cv';

  protected $fillable = [
    'version',
    'url',
    'user_id'
  ];

  protected $casts = [];

  protected $hidden = [
    'created_at',
    'updated_at',
  ];

  public function user()
  {
    return $this->belongsTo(User::class,'user_id');
  }

}
