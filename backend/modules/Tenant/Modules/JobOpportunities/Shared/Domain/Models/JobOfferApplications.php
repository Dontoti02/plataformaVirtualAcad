<?php

namespace Modules\Tenant\Modules\JobOpportunities\Shared\Domain\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Tenant\Modules\User\Models\User;

class JobOfferApplications extends Model
{
  use SoftDeletes;
  
  protected $table = 'job_opportunity_applications';

  protected $fillable = [
    'fullname',
    'program_study',
    'message',
    'status',
    'cv',
    'feedback',
    'feedback_date',
    'offer_id',
    'user_id',
  ];

  protected $casts = [
    'feedback_date' => 'datetime:d-m-Y H:i:s',
  ];

  protected $hidden = [
    'created_at',
    'updated_at',
  ];

  public function offer()
  {
    return $this->belongsTo(JobOffer::class, 'offer_id');
  }
  public function user()
  {
    return $this->belongsTo(User::class, 'user_id');
  }

  public function scopeHasPostulated($query, $userId)
  {
    return $query->where('user_id', $userId)->exists();
  }

  public function scopeByUserAndOffer($query, $userId, $offerId)
  {
    return $query->where('user_id', $userId)
      ->where('offer_id', $offerId)
      ->first();
  }
}
