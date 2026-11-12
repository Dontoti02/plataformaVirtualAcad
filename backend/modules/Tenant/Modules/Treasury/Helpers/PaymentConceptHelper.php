<?php

namespace Modules\Tenant\Modules\Treasury\Helpers;

use Modules\Tenant\Modules\Treasury\Enum\PaymentConceptEnum;
use Modules\Tenant\Modules\Treasury\Models\Movement;
use Modules\Tenant\Modules\Treasury\Models\PaymentConcept;
use Modules\Tenant\Modules\Treasury\Models\PaymentConceptHistory;

class PaymentConceptHelper {

  public static function getPaginated(int $page,int $limit,$search = null, string $sort = '') {
   
    $query = PaymentConcept::query();

    if ($search) {
      $query->where(function($q) use ($search) {
        $q->where('name', 'like', '%' . $search . '%')
          ->orWhere('code', 'like', '%' . $search . '%');
      });
    }

    $query->orderBy('code', 'asc');
    return $query->paginate($limit, ['*'], 'page', $page);

  }

  public static function find($id) {
    return PaymentConcept::find($id);
  }

  public static function exists($id) {
    return PaymentConcept::where('id', $id)->exists();
  }

  public static function hasEqualName($name, $id = null) {
    $query = PaymentConcept::where('name', $name);
    if ($id) {
      $query->where('id', '!=', $id);
    }
    return $query->exists();
  }

  public static function create(array $data) {
    $item = new PaymentConcept([
      'code' => PaymentConcept::generateCode(),
      'name' => $data['name'],
      'gross_amount' => $data['gross_amount'],
      'igv_amount' => $data['igv_amount'],
      'net_amount' => $data['net_amount'],
      'max_quotas' => $data['max_quotas'],
      'is_active' => true,
      'can_be_paid_in_quotas' => $data['can_be_paid_in_quotas'],
      'include_in_enrollment' => $data['include_in_enrollment'],
      'treasury_denomination_id' => $data['treasury_denomination_id']
    ]);
    $item->save();
    return $item->fresh();
  }

  public static function createHistory(array $data) {
    $paymentConcethistory  = New PaymentConceptHistory([
      'code' => $data['code'],
      'name' => $data['name'],
      'gross_amount' => $data['gross_amount'],
      'igv_amount' => $data['igv_amount'],
      'net_amount' => $data['net_amount'],
      'is_active' => true,
      'max_quotas' => $data['max_quotas'],
      'can_be_paid_in_quotas' => $data['can_be_paid_in_quotas'],
      'include_in_enrollment' => $data['include_in_enrollment'],
      'treasury_denomination_id' => $data['treasury_denomination_id'],
      'treasury_payment_concept_id' => $data['treasury_payment_concept_id'],
      'person_change_id' => $data['person_change_id'],
    ]);

    $paymentConcethistory->save();
    return $paymentConcethistory->fresh();
  }
  
  public static function search(String $search) {
    $paymentConcepts = PaymentConcept::select()
        ->where('name', 'like', "%$search%")
        ->orWhere('code', 'like', "%$search%")
        ->limit(10)
        ->get();

    return $paymentConcepts->map(function ($paymentConcept) {
      return [
        'id' => $paymentConcept->id,
        'name' => $paymentConcept->name,
        'code' => $paymentConcept->code,
        'gross_amount' => $paymentConcept->gross_amount,
        'igv_amount' => $paymentConcept->igv_amount,
        'net_amount' => $paymentConcept->net_amount,
        'max_quotas' => $paymentConcept->max_quotas,
        'can_be_paid_in_quotas' => $paymentConcept->can_be_paid_in_quotas,
        'include_in_enrollment' => $paymentConcept->include_in_enrollment,
        'treasury_denomination_id' => $paymentConcept->treasury_denomination_id,
        'is_active' => $paymentConcept->is_active,
      ];
    });
  }
  public static function enrollmentConceptsData() {
    return PaymentConcept::select()
      ->where('include_in_enrollment', true)
      ->where('is_active', true)
      ->whereIn('code', [PaymentConceptEnum::MATRICULA_CONCEPT_CODE, PaymentConceptEnum::PENSIONES_CONCEPT_CODE])
      ->get();
  }
  public static function history(int $id) {
    return PaymentConceptHistory::where('treasury_payment_concept_id', $id)
    ->with('personChange')
    ->with('denomination')
    ->with('paymentConcept')
    ->get();
  }

  public static function getMovements(int $id) {
    return Movement::where('treasury_payment_concept_id', $id)
    ->with('person')
    ->get();
  }
}
