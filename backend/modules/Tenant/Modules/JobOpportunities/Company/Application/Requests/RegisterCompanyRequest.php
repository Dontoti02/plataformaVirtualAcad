<?php

namespace Modules\Tenant\Modules\JobOpportunities\Company\Application\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterCompanyRequest extends FormRequest
{
  public function authorize(): bool
  {
    return true;
  }

  public function rules(): array
  {
    return [
      'ruc'         => ['required', 'digits:11', 'unique:job_opportunity_company,ruc'],
      'name'        => ['required', 'string', 'max:255'],
      'email'       => ['required', 'email', 'unique:user,email'],
      'phone'       => ['required'],
      'mailbox'     => ['required'],
      'description' => ['nullable', 'string'],
      'website'     => ['nullable', 'url'],
      'address'     => ['nullable', 'string'],
    ];
  }

  public function messages(): array
  {
    return [
      'ruc.required'         => 'El campo RUC es obligatorio.',
      'ruc.digits'           => 'El campo RUC debe tener 11 dígitos.',
      'ruc.unique'           => 'El RUC ya está registrado.',
      'name.required'        => 'El campo nombre es obligatorio.',
      'name.string'          => 'El campo nombre debe ser una cadena de texto.',
      'name.max'             => 'El campo nombre no puede tener más de 255 caracteres.',
      'email.required'       => 'El campo correo electrónico es obligatorio.',
      'email.email'          => 'El campo correo electrónico no es válido.',
      'email.unique'         => 'El correo electrónico ya está registrado.',
      'phone.required'       => 'El campo teléfono es obligatorio.',
      'mailbox.required'     => 'El campo buzón es obligatorio.',
      'description.string'   => 'El campo descripción debe ser una cadena de texto.',
      'website.url'          => 'El campo sitio web no es válido.',
      'address.string'       => 'El campo dirección debe ser una cadena de texto.',
    ];
  }
}
