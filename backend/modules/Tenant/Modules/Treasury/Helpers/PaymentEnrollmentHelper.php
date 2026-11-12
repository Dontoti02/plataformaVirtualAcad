<?php

namespace Modules\Tenant\Modules\Treasury\Helpers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Modules\Tenant\Modules\Enroll\Models\Enroll;
use Modules\Tenant\Modules\Enroll\Models\Scale;
use Modules\Tenant\Modules\Management\Helpers\PeriodHelper;
use Modules\Tenant\Modules\Treasury\Enum\PaymentConceptEnum;
use Modules\Tenant\Modules\Treasury\Models\Movement;
use Modules\Tenant\Modules\Treasury\Models\MovementDetails;
use Modules\Tenant\Modules\Treasury\Models\PaymentConcept;
use Modules\Tenant\Modules\User\Models\User;

class PaymentEnrollmentHelper {

  public static function payEnrollment(int $enrollId, Request $request, $generateCode) {

    $userId = $request->userId;

    $isExonerated = $request->isExonerated;

    $exonerationReason = $request->exonerationReason;

    $enrollment = Enroll::find($enrollId);

    $concept = PaymentConcept::where('code',PaymentConceptEnum::MATRICULA_CONCEPT_CODE)->first();
    
    if (!$concept) {
      throw new \Exception('El concepto de pago de matrícula no existe.');
    }

    $conceptId = $concept->id;
    
    $personId = $enrollment->person_id;
    
    $actualPeriodId = PeriodHelper::current()->id;
    
    $person_registration_id = User::find($userId)->person_id;
    
    $conceptAmount = $isExonerated == "true" ? $concept->gross_amount : $concept->net_amount;

    $pdfPath = null;
    if ($request->hasFile('movementVoucher')) {
      $movementVoucher = $request->file('movementVoucher');
      $pdfPath = is_array($movementVoucher) && isset($movementVoucher[0]) 
           ? $movementVoucher[0]->store('public/voucher') 
           : $movementVoucher->store('public/voucher');
    }

    $movement = Movement::create([
      'code' => $generateCode,
      'treasury_movement_type_id' => 1,
      'treasury_payment_concept_id' => $conceptId,
      'period_id' => $actualPeriodId,
      'person_id' => $personId,
      'person_registration_id' => $person_registration_id,
      'amount' => $conceptAmount,
      'initial_amount' => $conceptAmount,
      'amount_to_divide' => 0,
      'quotas' => 1,
      'is_paid' => 1,
      'is_exonerated' => $isExonerated == "true" ? 1 : 0,
      'exoneration_reason' => $exonerationReason,
      'remaining_amount' => 0,
      'due_date' => now(),
      'payment_date' => null
    ]);

    MovementDetails::create([
      'treasury_movement_id' => $movement->id,
      'treasury_payment_concept_id' => $conceptId,
      'person_registration_payment_id' => $personId,
      'person_created_schedule_by' => $person_registration_id,
      'amount' => ($conceptAmount),
      'is_paid' => 1,
      'due_date' => now(),
      'emission_date' => now(),
      'payment_date' => now(),
      'movement_voucher' => $pdfPath,
    ]);

    return $movement;
  }

  public static function payPension(int $enrollId, Request $request, $generateCode) {

    $userId = $request->userId;

    $isExonerated = $request->isExonerated;

    $exonerationReason = $request->exonerationReason;

    $enrollment = Enroll::find($enrollId);

    if (!$enrollment) {
      throw new \Exception('La matrícula no existe.');
    }

    $personId = $enrollment->person_id;

    $scaleId = $enrollment->scale_id;
    
    $scaleAmount = 0;

    if ($scaleId) {
      $scale = Scale::find($scaleId);
      if (!$scale) {
        throw new \Exception('La escala no existe.');
      }
      $scaleAmount = $scale->scale_amount;
    }

    $concept = PaymentConcept::where('code',PaymentConceptEnum::PENSIONES_CONCEPT_CODE)->first();

    if (!$concept) {
      throw new \Exception('El concepto de pago de pensiones no existe.');
    }

    $conceptId = $concept->id;

    $currentPeriod = PeriodHelper::current();

    if (!$currentPeriod) {
      throw new \Exception('El periodo actual no existe.');
    }

    $diffInMonths = ceil(Carbon::parse($currentPeriod->start_date)->diffInMonths(Carbon::parse($currentPeriod->end_date)));

    if ($diffInMonths == 0) {
      throw new \Exception('El periodo actual debe tener una diferencia minima de 2 meses entre fecha de inicio y fecha de fin.');
    }

    $quotaAmount = $diffInMonths;

    $actualPeriodId = $currentPeriod->id;

    $person_registration_id = User::find($userId)->person_id;

    if ($isExonerated == "true") {
      $conceptAmount = $concept->gross_amount;
    } else {
      $conceptAmount = $concept->net_amount;
    }

    $individualQuoteAmount = ($conceptAmount - $scaleAmount);

    $totalConceptAmount = $individualQuoteAmount * $quotaAmount;

    $pensionSelect = explode(',', $request->pensionSelect);

    $pensionPaid = collect($pensionSelect)->every(function ($pension) {
      return $pension === 'true';
    });
    
    $pensionPaidCount = collect($pensionSelect)->filter(function ($pension) {
      return $pension === 'true';
    })->count();

    $movement = Movement::create([
      'code' => $generateCode,
      'treasury_movement_type_id' => 1,
      'treasury_payment_concept_id' => $conceptId,
      'period_id' => $actualPeriodId,
      'person_id' => $personId,
      'person_registration_id' => $person_registration_id,
      'amount' => $totalConceptAmount,
      'initial_amount' => 0,
      'amount_to_divide' => $totalConceptAmount,
      'quotas' => $quotaAmount,
      'is_paid' => $pensionPaid ? 1 : 0,
      'remaining_amount' => $totalConceptAmount,
      'is_exonerated' => $isExonerated == "true" ? 1 : 0,
      'exoneration_reason' => $exonerationReason,
      'due_date' => now(),
      'payment_date' => null,
      'discounts' => $scaleAmount * $quotaAmount
    ]);


    $movementDetailsData = [];

    foreach (range(1, $quotaAmount) as $i) {
      
      $movementDetailsData[] = [
      'treasury_movement_id' => $movement->id,
      'treasury_payment_concept_id' => $conceptId,
      'person_registration_payment_id' => $personId,
      'person_created_schedule_by' => $person_registration_id,
      'amount' => $individualQuoteAmount,
      'is_paid' => $pensionSelect[$i - 1] == 'true' ? 1 : 0,
      'due_date' => now()->addMonths($i),
      'emission_date' => now(),
      'payment_date' => $pensionSelect[$i - 1] == 'true' ? now() : null,
      ];
    }

    MovementDetails::insert($movementDetailsData);

    $details = MovementDetails::where('treasury_movement_id', $movement->id)
    ->where('is_paid', 1)
    ->get();

    $movement->update([
      'is_paid' => $pensionPaidCount == $quotaAmount ? 1 : 0,
      'payment_date' => $pensionPaidCount == $quotaAmount ? now() : null
    ]);

    return $movement;
  }
}