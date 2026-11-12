<?php

namespace Modules\Tenant\Modules\Treasury\Repositories;

use Exception;
use Illuminate\Http\Request;
use Modules\Shared\Helpers\SessionManager;
use Modules\Tenant\Modules\SystemConfiguration\Helpers\SystemConfigurationHelper;
use Modules\Tenant\Modules\Treasury\Adapters\PaymentConceptAdapter;
use Modules\Tenant\Modules\Treasury\FormRequest\PaymentConceptForm;
use Modules\Tenant\Modules\Treasury\Helpers\MovementHelper;
use Modules\Tenant\Modules\Treasury\Helpers\PaymentConceptHelper;
use Modules\Tenant\Modules\User\Models\User;

class PaymentConceptRepository
{
  public static function all(Request $request)
  {

    $search = $request->input('search');
    $page   = (int) $request->input('page', 1);
    $limit  = (int) $request->input('limit', 10);

    $items = collect();

    $paginated = PaymentConceptHelper::getPaginated($page, $limit, $search);

    foreach ($paginated->items() as $item) {
      $items->push(PaymentConceptAdapter::transform($item));
    }
    $igv_amount = SystemConfigurationHelper::getIGV();

    return  [
      'items' => $items,
      'pagination' => [
        'page' => $paginated->currentPage(),
        'pages' => $paginated->lastPage(),
        'total' =>  $paginated->total(),
      ],
      'igv_amount' => $igv_amount
    ];
  }

  public static function create(Request $request)
  {

    $data = $request->all();

    $session = SessionManager::get();

    $userTenat = User::find($session->id);

    PaymentConceptForm::validateCreated($request);

    $paymentConcept = PaymentConceptHelper::create($data);

    $history = [
      ...$paymentConcept->toArray(),
      'treasury_payment_concept_id' => $paymentConcept->id,
      'person_change_id' => $userTenat->id
    ];

    PaymentConceptHelper::createHistory($history);

    return PaymentConceptAdapter::transform($paymentConcept);
  }

  public static function update(int $id, Request $request)
  {
    $data = $request->all();

    $session = SessionManager::get();

    $userTenat = User::find($session->id);

    PaymentConceptForm::validateUpdated($request, $id);

    $paymentConcept = PaymentConceptHelper::find($id);

    $saveHistory = ($data['gross_amount'] !== $paymentConcept->gross_amount
      || $data['igv_amount'] !== $paymentConcept->igv_amount
      || $data['net_amount'] !== $paymentConcept->net_amount
      || $data['max_quotas'] !== $paymentConcept->max_quotas
      || $data['can_be_paid_in_quotas'] !== $paymentConcept->can_be_paid_in_quotas);

    $paymentConcept->update($data);

    if ($saveHistory) {
      $history = [
        ...$paymentConcept->toArray(),
        'treasury_payment_concept_id' => $paymentConcept->id,
        'person_change_id' => $userTenat->id
      ];

      PaymentConceptHelper::createHistory($history);
    }

    return PaymentConceptAdapter::transform($paymentConcept);
  }

  public static function active(int $id)
  {
    PaymentConceptForm::exists($id);

    $paymentConcept = PaymentConceptHelper::find($id);

    $paymentConcept->update(['is_active' => true]);

    return PaymentConceptAdapter::transform($paymentConcept);
  }

  public static function inactive(int $id)
  {
    PaymentConceptForm::exists($id);

    $paymentConcept = PaymentConceptHelper::find($id);

    if (MovementHelper::hasMovementsPendingPayment($paymentConcept->id)) {
      throw new Exception('No puedes desactivar este concepto de pago, existen movimientos pendientes de pago para este concepto.');
    }

    $paymentConcept->update(['is_active' => false]);

    return PaymentConceptAdapter::transform($paymentConcept);
  }

  public static function delete(int $id)
  {
    PaymentConceptForm::exists($id);

    $paymentConcept = PaymentConceptHelper::find($id);

    if (MovementHelper::hasMovementsPendingPayment($paymentConcept->id)) {
      throw new Exception('No puedes eliminar este concepto de pago, existen movimientos pendientes de pago para este concepto.');
    }

    $paymentConcept->delete();

    return PaymentConceptAdapter::transform($paymentConcept);
  }
  
  public static function search(Request $request)
  {
    $search = $request->search;

    $paymentConcepts = PaymentConceptHelper::search($search)
    ->where('is_active', true);
    return  $paymentConcepts->map(function ($paymentConcept) {
      return [
        'id' => $paymentConcept['id'],
        'name' => $paymentConcept['name'],
        'code' => $paymentConcept['code'],
        'gross_amount' => $paymentConcept['gross_amount'],
        'igv_amount' => $paymentConcept['igv_amount'],
        'net_amount' => $paymentConcept['net_amount'],
        'max_quotas' => $paymentConcept['max_quotas'],
        'can_be_paid_in_quotas' => $paymentConcept['can_be_paid_in_quotas'],
        'include_in_enrollment' => $paymentConcept['include_in_enrollment'],
        'treasury_denomination_id' => $paymentConcept['treasury_denomination_id'],
        'is_active' => $paymentConcept['is_active'],
        'names' => $paymentConcept['code'] . ' - ' . $paymentConcept['name']
      ];
    });
  }
  public static function enrollmentConceptsData()
  {
    $paymentConcepts = PaymentConceptHelper::enrollmentConceptsData();
    return  $paymentConcepts;
  }
  public static function history(int $id)
  {
    $paymentConcept = PaymentConceptHelper::find($id);
    $history = PaymentConceptHelper::history($id);
    return  $history;
  }
  public static function movements(int $id)
  {
    $movements = PaymentConceptHelper::getMovements($id);
    return  $movements;
  }

}
