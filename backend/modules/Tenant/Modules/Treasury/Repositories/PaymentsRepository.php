<?php

namespace Modules\Tenant\Modules\Treasury\Repositories;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Modules\Shared\Services\CentralConfig;
use Modules\Shared\Helpers\SessionManager;
use Modules\Tenant\Modules\Enroll\Models\Enroll;
use Modules\Tenant\Modules\Enroll\Models\Scale;
use Modules\Tenant\Modules\Management\Helpers\PeriodHelper;
use Modules\Tenant\Modules\Treasury\Helpers\BoletaHelper;
use Modules\Tenant\Modules\Treasury\Helpers\PaymentEnrollmentHelper;
use Modules\Tenant\Modules\Treasury\Models\Movement;
use Modules\Tenant\Modules\Treasury\Models\MovementDetails;
use Modules\Tenant\Modules\Treasury\Models\PaymentConcept;
use Modules\Tenant\Modules\User\Models\Person;
use Modules\Tenant\Modules\User\Models\User;

class PaymentsRepository
{
  public static function get(int $person_id, int $is_paid)
  {
    $page = request()->get('page', 1);
    $perPage = request()->get('itemsPerPage', 10);
    $type = request()->get('type');
    $period = request()->get('period');

    $query = Movement::where('person_id', $person_id)
      ->with('paymentConcept')
      ->with('movementType')
      ->with(['MovementDetails' => function ($query) {
        $query->orderBy('due_date', 'asc');
      }])
      ->when($period !== 'undefined', function ($query) use ($period) {
        return $query->where('period_id', $period);
      })
      ->when($period === 'undefined', function ($query) use ($period) {
        $actualPeriod = PeriodHelper::current()->id;
        return $query->where('period_id', $actualPeriod);
      })

      ->when($type !== 'undefined', function ($query) use ($type) {
        return $query->where('treasury_movement_type_id', $type);
      })
      ->where('is_paid', $is_paid)
      ->whereNull('deleted_at')
      ->orderByRaw('is_paid ASC, due_date ASC');

    $total = $query->count();
    $result = $query->skip(($page - 1) * $perPage)->take($perPage)->get();

    $result = $result->map(function ($item) {
      
      $nextPaymentDate = $item->movementDetails->where('is_paid', 0)->first()->due_date ?? null;

      $totalAmount = $item->amount;

      return [
        'payment_date' => $item->payment_date ?? null,
        'concept' => $item->paymentConcept->name ?? null,
        'amount' => $item->remaining_amount,
        'due_date' => $nextPaymentDate,
        'remaining_payments' => $item->movementDetails->where('is_paid', 1)->count() . '/' . $item->quotas,
        'total_amount' => $totalAmount,
        'status' => $item->is_paid ? 'Pago Completado' : 'Deuda',
        'type' => $item->movementType->name ?? null,
        'movement_id' => $item->id,
        'code' => $item->code,
        'details' => $item->movementDetails->map(function ($movement, $index) {
          $movement->index = $index + 1;
          return $movement;
        })
      ];
    });
    return [
      'data' => $result,
      'total' => $total,
      'per_page' => $perPage,
      'current_page' => $page,
      'last_page' => ceil($total / $perPage),
      'periods' => PeriodHelper::list(),
    ];
  }

  public static function SearchStudent(Request $request)
  {
    $search = $request->search;
    $students = Person::select()
      ->where('names', 'like', "%$search%")
      ->orWhere('document_number', 'like', "%$search%")
      ->limit(10)
      ->whereNull('deleted_at')
      ->get();
    $studentData = $students->map(function ($student) {
      return [
        'person_id' => $student->id,
        'gender' => $student->sex,
        'document_number' => $student->document_number,
        'name' => $student->names,
        'names' => $student->document_number . ' - ' . $student->names
      ];
    });
    return $studentData;
  }

  public static function create(Request $request)
  {
    $conceptId = $request->concept;
    $initialAmount = $request->initialAmount;
    $quotaAmount = $request->quota;
    $actualPeriodId = PeriodHelper::current()->id;
    $userId = $request->userId;
    $movementType = $request->movementType;
    $person_registration_id = User::find($userId)->person_id;
    $person_id = $request->personId;
    $is_exonerated = $request->isExonerated;
    $exoneration_reason = $request->exonerationReason;
    if ($is_exonerated == true) {
      $conceptAmount = PaymentConcept::find($conceptId)->gross_amount;
    } else {
      $conceptAmount = PaymentConcept::find($conceptId)->net_amount;
    }
    $generateCode = self::generateCode($movementType);

    $movement = Movement::create([
      'code' => $generateCode,
      'treasury_movement_type_id' => $movementType,
      'treasury_payment_concept_id' => $conceptId,
      'period_id' => $actualPeriodId,
      'person_id' => $person_id,
      'person_registration_id' => $person_registration_id,
      'amount' => $conceptAmount,
      'initial_amount' => $initialAmount,
      'amount_to_divide' => ($conceptAmount - $initialAmount),
      'quotas' => $quotaAmount,
      'is_paid' => 0,
      'is_exonerated' => $is_exonerated,
      'exoneration_reason' => $exoneration_reason,
      'remaining_amount' => ($conceptAmount - $initialAmount),
      'due_date' => now(),
      'payment_date' => null
    ]);


    for ($i = 1; $i <= $quotaAmount; $i++) {
      MovementDetails::create([
        'treasury_movement_id' => $movement->id,
        'treasury_payment_concept_id' => $conceptId,
        'person_registration_payment_id' => $person_id,
        'person_created_schedule_by' => $person_registration_id,
        'amount' => (($conceptAmount - $initialAmount) / $quotaAmount),
        'is_paid' => 0,
        'due_date' => now()->addMonths($i),
        'emission_date' => now(),
        'payment_date' => null
      ]);
    }
    return $movement;
  }

  public static function generateCode($type)
  {
    $prefix = $type === 1 ? 'MI' : 'ME';
    $lastMovement = Movement::withTrashed()->where('code', 'like', "$prefix-%")->orderBy('code', 'desc')->first();
    $lastNumber = $lastMovement ? intval(substr($lastMovement->code, 3)) : 0;
    $newNumber = str_pad($lastNumber + 1, 6, '0', STR_PAD_LEFT);
    return "$prefix-$newNumber";
  }

  public static function detail(int $id)
  {
    $movements = MovementDetails::where('treasury_movement_id', $id)
      ->orderBy('due_date', 'asc')
      ->whereNull('deleted_at')
      ->get()
      ->map(function ($movement, $index) {
        $movement->index = $index + 1;
        return $movement;
      });

    $nextMovements = $movements->where('is_paid', 0)->values()->all();
    $payedMovements = $movements->where('is_paid', 1)->values()->all();

    return [
      'nextMovements' => $nextMovements,
      'payedMovements' => $payedMovements
    ];
  }

  public static function payNextDetail(int $id)
  {
    $Movement = Movement::find($id);
    $nextMovement = MovementDetails::where('treasury_movement_id', $id)
      ->where('is_paid', 0)
      ->orderBy('due_date', 'asc')
      ->first();
    $nextMovement->update(['is_paid' => 1]);
    $nextMovement->update(['payment_date' => now()]);

    $remainingMovements = MovementDetails::where('treasury_movement_id', $id)
      ->where('is_paid', 0)
      ->orderBy('due_date', 'asc')
      ->count();

    $Movement->update(['remaining_amount' => ($remainingMovements == 0) ? 0 : ($Movement->remaining_amount - $nextMovement->amount)]);
    $Movement->update(['payment_date' => now()]);
    $Movement->update(['is_paid' => ($Movement->remaining_amount == 0) ? 1 : 0]);
    return $nextMovement;
  }

  public static function exportTicket(
    int $paymentDetailId
  ) {

    $institution = CentralConfig::institution();

    $info = BoletaHelper::info(
      $institution,
      $paymentDetailId
    );

    $pdf = Pdf::loadView('treasury::pdf.boleta', ['info' => $info]);

    return $pdf;
  }
  
  public static function exportMovementTicket(
    int $movementId
  ) {

    $institution = CentralConfig::institution();

    

    $info = BoletaHelper::movementInfo(
      $institution,
      $movementId
    );

    $pdf = Pdf::loadView('treasury::pdf.boleta', ['info' => $info]);

    return $pdf;
  }

  public static function movementsByConcept(int $conceptId)
  {
    $personId = request()->get('personId');
    $movements = Movement::where('treasury_payment_concept_id', $conceptId)
      ->where('person_id', $personId)
      ->whereNotNull('payment_date',)
      ->where('treasury_movement_type_id', 1)
      ->whereNull('refund_movement_id')
      ->get();
    return $movements;
  }

  public static function refund(Request $request)
  {
    $personId = $request->personId;
    $amount = $request->amount;
    $userId = $request->userId;
    $movementIds = $request->movementIds;
    $conceptId = $request->conceptId;
    $movementType = $request->movementType;
    $generateCode = self::generateCode($movementType);

    $refundMovement = Movement::create([
      'code' => $generateCode,
      'treasury_movement_type_id' => $movementType,
      'treasury_payment_concept_id' => $conceptId,
      'period_id' => PeriodHelper::current()->id,
      'person_id' => $personId,
      'person_registration_id' => User::find($userId)->person_id,
      'amount' => $amount,
      'initial_amount' => $amount,
      'amount_to_divide' => 0,
      'quotas' => 1,
      'is_paid' => 1,
      'remaining_amount' => 0,
      'due_date' => now(),
      'payment_date' => now()
    ]);

    if ($movementIds == null) {
      return $refundMovement;
    }

    $MovementsToRefund = Movement::whereIn('id', $movementIds)->get();
    foreach ($MovementsToRefund as $movement) {
      $movement->update(['refund_movement_id' => $movement->id]);
      $movement->delete();
      $details = MovementDetails::where('treasury_movement_id', $movement->id)->get();
      foreach ($details as $detail) {
        $detail->delete();
      }
    }

    return $refundMovement;
  }

  public static function payEnrollment(int $enrollId, Request $request) {

    $type = $request->type;

    $movement = null;

    // pay only enrollment
    if ($type == 1) {
      $movement = PaymentEnrollmentHelper::payEnrollment(
        $enrollId, $request,
        self::generateCode(1)
      );
          
    // pay only pension
    } else if ($type == 2) {  

      $movement = PaymentEnrollmentHelper::payPension(
        $enrollId, $request,
        self::generateCode(1)
      );

    } else if ($type == 3) {
      //Se registran los pagos de pensiones y matricula

      // //pago de matricula
      // $userId = $request->userId;
      // $generateCode = self::generateCode(1);
      // $enrollment = Enroll::find($enrollId);
      // $concept = PaymentConcept::whereRaw('LOWER(name) LIKE ?', ['%matricula%'])->first();
      // $conceptId = $concept->id;
      // $personId = $enrollment->person_id;
      // $quotaAmount = $concept->max_quotas;
      // $actualPeriodId = PeriodHelper::current()->id;
      // $person_registration_id = User::find($userId)->person_id;
      // if ($isExonerated == "true") {
      //   $conceptAmount = $concept->gross_amount;
      // } else {
      //   $conceptAmount = $concept->net_amount;
      // }
      // if ($request->hasFile('movementVoucher')) {
      //   $movementVoucher = $request->file('movementVoucher');
      //   if (is_array($movementVoucher) && isset($movementVoucher[0])) {
      //     $pdfPath = $movementVoucher[0]->store('public/voucher');
      //   } else {
      //     $pdfPath = $movementVoucher->store('public/voucher');
      //   }
      // } else {
      //   $pdfPath = null;
      // }

      // $movement = Movement::create([
      //   'code' => $generateCode,
      //   'treasury_movement_type_id' => 1,
      //   'treasury_payment_concept_id' => $conceptId,
      //   'period_id' => $actualPeriodId,
      //   'person_id' => $personId,
      //   'person_registration_id' => $person_registration_id,
      //   'amount' => $conceptAmount,
      //   'initial_amount' => 0,
      //   'amount_to_divide' => ($conceptAmount),
      //   'quotas' => 1,
      //   'is_exonerated' => $isExonerated == "true" ? 1 : 0,
      //   'exoneration_reason' => $exonerationReason,
      //   'is_paid' => 0,
      //   'remaining_amount' => ($conceptAmount),
      //   'due_date' => now()->addMonths(1),
      //   'payment_date' => null
      // ]);

      // MovementDetails::create([
      //   'treasury_movement_id' => $movement->id,
      //   'treasury_payment_concept_id' => $conceptId,
      //   'person_registration_payment_id' => $personId,
      //   'person_created_schedule_by' => $person_registration_id,
      //   'amount' => ($conceptAmount),
      //   'is_paid' => 0,
      //   'due_date' => now(),
      //   'emission_date' => now(),
      //   'payment_date' => null,
      //   'movement_voucher' => $pdfPath
      // ]);
      // //pago de pensiones
      // $userId = $request->userId;
      // $generateCode = self::generateCode(1);
      // $enrollment = Enroll::find($enrollId);
      // $concept = PaymentConcept::whereRaw('LOWER(name) LIKE ?', ['%pension%'])->first();
      // $conceptId = $concept->id;
      // $personId = $enrollment->person_id;
      // $quotaAmount = $concept->max_quotas;
      // $actualPeriodId = PeriodHelper::current()->id;
      // $person_registration_id = User::find($userId)->person_id;
      // $scaleId = $enrollment->scale_id;
      // $scaleAmount = $scaleId ? Scale::find($scaleId)->scale_amount : 0;
      // if ($isExonerated == "true") {
      //   $conceptAmount = $concept->gross_amount;
      // } else {
      //   $conceptAmount = $concept->net_amount;
      // }
      // $pensionSelect = explode(',', $request->pensionSelect);
      // $pensionPaid = collect($pensionSelect)->every(function ($pension) {
      //   return $pension === 'true';
      // });
      // $pensionPaidCount = collect($pensionSelect)->filter(function ($pension) {
      //   return $pension === 'true';
      // })->count();
      // if ($pensionPaidCount == 0) {
      //   $initialAmount = 0;
      // } else {
      //   $initialAmount = (($conceptAmount - $scaleAmount) / ($quotaAmount)) * $pensionPaidCount;
      // }

      // $movement = Movement::create([
      //   'code' => $generateCode,
      //   'treasury_movement_type_id' => 1,
      //   'treasury_payment_concept_id' => $conceptId,
      //   'period_id' => $actualPeriodId,
      //   'person_id' => $personId,
      //   'person_registration_id' => $person_registration_id,
      //   'amount' => ($conceptAmount - $scaleAmount),
      //   'initial_amount' => $initialAmount,
      //   'amount_to_divide' => ($conceptAmount - $scaleAmount - $initialAmount),
      //   'quotas' => $quotaAmount,
      //   'is_exonerated' => $isExonerated == "true" ? 1 : 0,
      //   'exoneration_reason' => $exonerationReason,
      //   'is_paid' => $pensionPaid ? 1 : 0,
      //   'remaining_amount' => ($conceptAmount - $scaleAmount - $initialAmount),
      //   'due_date' => now(),
      //   'payment_date' => null
      // ]);

      // for ($i = 1; $i <= $quotaAmount; $i++) {
      //   MovementDetails::create([
      //     'treasury_movement_id' => $movement->id,
      //     'treasury_payment_concept_id' => $conceptId,
      //     'person_registration_payment_id' => $personId,
      //     'person_created_schedule_by' => $person_registration_id,
      //     'amount' => ($conceptAmount - $scaleAmount) / $quotaAmount,
      //     'is_paid' => $pensionSelect[$i - 1] == 'true' ? 1 : 0,
      //     'due_date' => now()->addMonths($i),
      //     'emission_date' => now(),
      //     'payment_date' => $pensionSelect[$i - 1] == 'true' ? now() : null
      //   ]);
      // }

      // return $movement;
    }

    return $movement;
  }
}
