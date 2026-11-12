<?php

namespace Modules\Tenant\Modules\JobOpportunities\JobOffer\Application\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Tenant\Modules\JobOpportunities\JobOffer\Application\Enums\JobOfferStateEnum;

class ChangeOfferStatusRequest extends FormRequest
{


  public function rules(): array
  {
    return [
      'new_status' => 'required|string|in:'.implode(',', [
        JobOfferStateEnum::DRAFT,
        JobOfferStateEnum::ACTIVE,
        JobOfferStateEnum::FINISHED,
        JobOfferStateEnum::SUSPENDED,
        JobOfferStateEnum::CANCELED,
      ]),
      'id' => 'required|integer|exists:job_opportunity_offer,id',
    ];
  }
}
