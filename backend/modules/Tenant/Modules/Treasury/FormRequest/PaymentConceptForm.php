<?php

namespace Modules\Tenant\Modules\Treasury\FormRequest;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Modules\Tenant\Modules\Treasury\Helpers\PaymentConceptHelper;

class PaymentConceptForm
{

  public static function validateCreated(Request $request)
  {
    if (PaymentConceptHelper::hasEqualName($request['name'])) {
      throw new Exception("Ya existe un concepto de pago con el nombre ingresado");
    }

    $validator = Validator::make($request->all(), [
      "name"                    => "required|string",
      "gross_amount"            => "required|numeric",
      "igv_amount"              => "required|numeric",
      "net_amount"              => "required|numeric",
      "max_quotas"              => "required|integer",
      'can_be_paid_in_quotas'   => 'required|boolean',
      'include_in_enrollment'   => 'required|boolean',
      'treasury_denomination_id' => 'required|exists:treasury_denomination,id',
    ]);

    if ($validator->fails()) {
      throw new Exception($validator->errors()->first());
    }
  }

  public static function exists($id)
  {
    if (!PaymentConceptHelper::exists($id)) {
      throw new Exception("El concepto de pago no existe");
    }
  }

  public static function validateUpdated(Request $request, int $exceptId)
  {
    self::exists($exceptId);

    if (PaymentConceptHelper::hasEqualName($request['name'], $exceptId)) {
      throw new Exception("Ya existe un concepto de pago con el nombre ingresado");
    }

    $validator = Validator::make($request->all(), [
      "name"                    => "required|string",
      "gross_amount"            => "required|numeric",
      "igv_amount"              => "required|numeric",
      "net_amount"              => "required|numeric",
      "max_quotas"              => "required|integer",
      'can_be_paid_in_quotas'   => [
        'required',
        'boolean',
      ],
      'include_in_enrollment'   => 'required|boolean',
      'treasury_denomination_id' => 'required|exists:treasury_denomination,id',
      'code' => 'required|string',
    ]);

    $validator->after(function ($validator) use ($request) {
      
      $restrictedCodes = ['PC-0001', 'PC-0002'];

      if (in_array($request->code, $restrictedCodes)) {
          if ($request->can_be_paid_in_quotas != false) {
              $validator->errors()->add('can_be_paid_in_quotas', 'Este concepto de pago no puede pagarse en cuotas.');
          }

          if ($request->max_quotas != 1) {
              $validator->errors()->add('max_quotas', 'Máximo de cuotas debe ser 1 para este concepto de pago.');
          }
      }
  });

    if ($validator->fails()) {
      throw new Exception($validator->errors()->first());
    }
  }
}
