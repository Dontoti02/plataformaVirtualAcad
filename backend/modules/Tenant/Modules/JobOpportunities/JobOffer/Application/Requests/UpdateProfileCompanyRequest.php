<?php

namespace Modules\Tenant\Modules\JobOpportunities\JobOffer\Application\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileCompanyRequest extends FormRequest
{
  public function authorize(): bool
  {
    return true;
  }

  public function rules(): array
  {
    return [
      'name'        => ['required', 'string', 'max:255'],
      'email'       => ['required', 'email', 'max:255'],
      'phone'       => ['required', 'string', 'max:20'],
      'ruc'         => ['required', 'string', 'max:20'],
      'description' => ['nullable', 'string'],
      'mailbox'     => ['required', 'string'],
      'website'     => ['required', 'url', 'max:255'],
      'address'     => ['required', 'string', 'max:255']
    ];
  }
}
