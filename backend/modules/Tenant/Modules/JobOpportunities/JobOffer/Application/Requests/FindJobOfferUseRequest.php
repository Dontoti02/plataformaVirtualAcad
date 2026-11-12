<?php

namespace Modules\Tenant\Modules\JobOpportunities\JobOffer\Application\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Shared\Helpers\SessionManager;
use Modules\Tenant\Modules\JobOpportunities\Shared\Domain\Models\JobOffer;
use Modules\Tenant\Modules\User\Enums\RolTenant;
use Modules\Tenant\Modules\User\Models\User;

class FindJobOfferUseRequest extends FormRequest
{
  public function authorize(): bool
  {
    $session = SessionManager::get();
    $user = User::find($session->id);
    $isAdmin = $user->hasRole(RolTenant::ADMINISTRADOR);

    $jobOfferId = $this->query('id');
    $jobOfferSlug = $this->query('slug');

    if ($isAdmin) {
      return true;
    }

    if (!$user->company) {
      return false;
    }

    $query = JobOffer::query()->where('company_id', $user->company->id);

    if ($jobOfferSlug || $jobOfferId) {
      $query->when($jobOfferSlug, fn($q) => $q->where('slug', $jobOfferSlug))
        ->when($jobOfferId, fn($q) => $q->where('id', $jobOfferId));
    } else {
      return false;
    }

    if ($query->exists()) {
      return true;
    }
    return false;
  }

  public function rules(): array
  {
    return [];
  }
}
