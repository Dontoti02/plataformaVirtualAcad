<?php

namespace Modules\Tenant\Modules\JobOpportunities\JobOffer\Application\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\UnauthorizedException;
use Modules\Shared\Helpers\SessionManager;
use Modules\Tenant\Modules\User\Enums\RolTenant;
use Modules\Tenant\Modules\User\Models\User;

class CreateJobOfferRequest extends FormRequest
{
  public function authorize(): bool
  {
    $session = SessionManager::get();
    $user = User::find($session->id);
    $isAdmin = $user->hasRole(RolTenant::ADMINISTRADOR);
    $isCompany = $user->hasRole(RolTenant::COMPANY);
    if ($isAdmin || $isCompany) {
      return true;
    }
    abort(403, 'Acceso no autorizado');
    throw new UnauthorizedException('Acceso no autorizado');
  }

  public function rules(): array
  {
    return [
      'title'            => ['required', 'string', 'max:255'],
      'description'      => ['required', 'string'],
      'requirements'     => ['required', 'string'],
      'benefits'         => ['required', 'string'],
      'companyId'        => ['nullable', 'integer'],
      'categoryId'       => ['nullable', 'integer'],
      'locationId'       => ['nullable', 'integer'],
      'contractTypeId'   => ['nullable', 'integer'],
      'scheduleId'       => ['nullable', 'integer'],
      'salary'           => ['required', 'numeric'],
      'salaryCurrency'   => ['required', 'string', 'max:10'],
      'address'          => ['required', 'string', 'max:255'],
      'department'       => ['required', 'string', 'max:100'],
      'province'         => ['required', 'string', 'max:100'],
      'country'          => ['required', 'string', 'max:100'],
      'publicationDate'  => ['required', 'date'],
      'attachments'      => ['nullable', 'array'],
      'attachments.*'    => ['file'],
    ];
  }
}
