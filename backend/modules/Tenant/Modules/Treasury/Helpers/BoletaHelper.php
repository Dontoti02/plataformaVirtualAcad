<?php

namespace Modules\Tenant\Modules\Treasury\Helpers;

use Exception;
use Modules\Admin\Models\Institution;
use Modules\Tenant\Modules\SystemConfiguration\Helpers\SystemConfigurationHelper;
use Modules\Tenant\Modules\Treasury\Models\Movement;
use Modules\Tenant\Modules\Treasury\Models\MovementDetails;
use Modules\Tenant\Modules\Treasury\Models\PaymentConcept;
use Modules\Tenant\Modules\User\Models\Person;
use Luecano\NumeroALetras\NumeroALetras;
use Modules\Admin\Models\SystemConfiguration;
use Modules\Tenant\Modules\Treasury\Enum\PaymentConceptEnum;

class BoletaHelper {
  public static function info(
    Institution $institution,
    $paymentDetailId = null
  ) {
    $emails = SystemConfigurationHelper::getValueByKey('support_emails');

    $paymentDetail = MovementDetails::where('id', $paymentDetailId)->first();

    $latinaEducaDomain = SystemConfiguration::byKey('key','domain');

    if (!$paymentDetail) {
      throw new Exception('Detalle de pago no encontrado');
    }

    $payment = $paymentDetail->movement;

    if (!$payment) {
      throw new Exception('Pago no encontrado');
    }

    $paymentConcept = PaymentConcept::find($paymentDetail->treasury_payment_concept_id);

    if (!$paymentConcept) {
      throw new Exception('Concepto de pago no encontrado');
    }

    $client = Person::where('id', $payment->person_id)
    ->with('aditionalData')
    ->first();

    if (!$client) {
      throw new Exception('Cliente no encontrado');
    }

    $quotaPaid = self::getNumQuotePayment($payment->id, $paymentDetailId);
 
    $totalAmount = $paymentDetail->amount;

    $text_amount = self::getAmountInWords($totalAmount);

    $data = (Object)[
      'company' => (Object)[
        'logo' => $institution->logo,
        'name' => $institution->name,
        'ruc' => $institution->ruc,
        'address' => $institution->address,
        'department' => $institution->department,
        'province' => $institution->province,
        'district' => $institution->district,
        'emails' => $emails,
      ],
      'latinaeduca' => (Object) [
        'domain' => $latinaEducaDomain->value
      ],
      'payment' => (Object)[
        'code' => $payment->code,
        'amount' => $payment->amount,
        'payment_date' => $payment->payment_date
      ], 
      'client' => (Object)[
        'name' => $client->names,
        'document_type' => $client->document_type,
        'document_number' => $client->document_number,
        'phone' => $client->phone,
        'address' => $client->aditionalData?->current_address,
      ],
      'detail' => (Object)[
        (Object)[
         'description' => $paymentConcept->name,
          'quota' =>  $quotaPaid .'/' . $payment->quotas,
          'amount' => $paymentDetail->amount,
        ],
      ],
      'initial_amount' => $payment->initial_amount,
      'is_igv_exonerated' => true,
      'igv_amount' => $paymentConcept->igv_amount,
      'gross_amount' => $paymentConcept->gross_amount,
      'net_amount' => $paymentConcept->netAmount,
      'discounts' => $payment->discounts,
      'total_amount' => $totalAmount,
      'total_amount_text' => $text_amount
    ];

    return $data;
  }

  public static function movementInfo(
    Institution $institution,
    $paymentId = null
  ) {
    
    $emails = SystemConfigurationHelper::getValueByKey('support_emails');

    $payment = Movement::where('id', $paymentId)->first();

    $latinaEducaDomain = SystemConfiguration::byKey('key','domain');

    if (!$payment) {
      throw new Exception('Pago no encontrado');
    }

    $paymentConcept = PaymentConcept::find($payment->treasury_payment_concept_id);

    if (!$paymentConcept) {
      throw new Exception('Concepto de pago no encontrado');
    }

    $client = Person::where('id', $payment->person_id)
    ->with('aditionalData')
    ->first();

    if (!$client) {
      throw new Exception('Cliente no encontrado');
    }

    $totalAmount = $payment->amount - $payment->remaining_amount;
    
    $paymentDetails = $payment->MovementDetails()->orderBy('id', 'asc')
    ->get()
    ->map(function($item) use ($payment) {
      return (Object)[
        'description' => $item->paymentConcept->name,
        'quota' =>  self::getNumQuotePayment($payment->id,$item->id) .'/' . $payment->quotas,
        'amount' => $item->amount,
      ];
    });

    if ($payment->initial_amount > 0) {
      $paymentDetails->push((Object)[
        'description' => 'ADELANTO DE CUOTA',
        'quota' =>  '',
        'amount' => $payment->initial_amount,
      ]);
    }

    $netAmount = $paymentConcept->net_amount;
    $igvAmount = $paymentConcept->igv_amount;
    $grossAmount = $paymentConcept->gross_amount;

    if ($paymentConcept->code === PaymentConceptEnum::PENSIONES_CONCEPT_CODE) {
      $netAmount = $paymentConcept->net_amount * $payment->quotas;
      $igvAmount = $paymentConcept->igv_amount * $payment->quotas;
      $grossAmount = $paymentConcept->gross_amount * $payment->quotas;
    }

    $discounts = $payment->discounts;

    if ($discounts > 0 ) {
      $paymentDetails->push((Object)[
        'description' => 'Descuentos(por escala,etc)',
        'quota' =>  '1/1',
        'amount' => -$discounts,
      ]);
    }

    $text_amount = self::getAmountInWords($totalAmount);
    
    $data = (Object)[
      'company' => (Object)[
        'logo' => $institution->logo,
        'name' => $institution->name,
        'ruc' => $institution->ruc,
        'address' => $institution->address,
        'department' => $institution->department,
        'province' => $institution->province,
        'district' => $institution->district,
        'emails' => $emails
      ],
      'latinaeduca' => (Object) [
        'domain' => $latinaEducaDomain->value
      ],
      'payment' => (Object)[
        'code' => $payment->code,
        'amount' => $payment->amount,
        'payment_date' => $payment->payment_date
      ], 
      'client' => (Object)[
        'name' => $client->names,
        'document_type' => $client->document_type,
        'document_number' => $client->document_number,
        'phone' => $client->phone,
        'address' => $client->aditionalData? $client->aditionalData->current_address : '',
      ],
      'is_igv_exonerated' => $payment->is_exonerated,
      'igv_amount' => $igvAmount,
      'gross_amount' => $grossAmount,
      'net_amount' => $netAmount,
      'discounts' => $discounts,
      'detail' => $paymentDetails,
      'total_amount' => $totalAmount,
      'total_amount_text' => $text_amount
    ];

    return $data;
  }


  public static function getNumQuotePayment($paymentId,$paymentDetailId) {
    
    $payment = Movement::where('id', $paymentId)->first();

    if (!$payment) {
      throw new Exception('Pago no encontrado');
    }

    $allQuotes = $payment->MovementDetails()->orderBy('id', 'asc')->get();

    $quoteIndex = $allQuotes->pluck('id')->search($paymentDetailId);

    if ($quoteIndex === false) {
      throw new Exception('Detalle de pago no encontrado en las cuotas');
    }

    $quoteNumber = $quoteIndex + 1;

    return $quoteNumber;
  }

  public static function getNumQuotePaymentMovement($paymentId) {
    
    
    $paid_quotes = MovementDetails::where('treasury_movement_id', $paymentId)
    ->where('is_paid', 1)
    ->orderBy('id', 'asc')
    ->count();

    return $paid_quotes;
  }

  public static function getLineStartsByLength($length = 80,$character = "*", $label = 'Label') {
    $lengthInChars = intval($length);
    $padding = ($lengthInChars - strlen($label)) / 2;
    $line = str_repeat($character, floor($padding)) . $label . str_repeat($character, ceil($padding));
    return $line;
  }

  public static function getAmountInWords($amount) {
    $formatter = new NumeroALetras();
    return $formatter->toMoney($amount, 2, 'SOLES', 'CENTIMOS');
  }
}