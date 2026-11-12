<?php

namespace Modules\Tenant\Modules\JobOpportunities\JobOffer\Application\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Shared\Helpers\SessionManager;
use Modules\Tenant\Modules\JobOpportunities\Shared\Domain\Models\JobOffer;
use Modules\Tenant\Modules\User\Enums\RolTenant;
use Modules\Tenant\Modules\User\Models\User;

class DeleteJobOfferUseRequest extends FormRequest
{
  public function authorize(): bool
  {
    $session = SessionManager::get();
    $user = User::find($session->id);
    $isAdmin = $user->hasRole(RolTenant::ADMINISTRADOR);

    if ($isAdmin) {
      return true;
    }
    $jobOfferId = $this->route('id');
    $findJobOffer = JobOffer::query()
      ->where('company_id', $user->company->id)
      ->where('id', $jobOfferId)
      ->exists();
    if ($findJobOffer) {
      return true;
    }
    abort(403, 'No tienes permiso para acceder a esta oferta de trabajo.');
    return false;
  }

  public function rules(): array
  {
    return [];
  }
}
