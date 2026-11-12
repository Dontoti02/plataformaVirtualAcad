<?php

namespace Modules\Tenant\Modules\Treasury\Helpers;

use Modules\Tenant\Modules\Treasury\Models\Movement;
use Modules\Tenant\Modules\Treasury\Models\MovementType;

class MovementHelper {
  public static function hasMovementsPendingPayment($paymentConceptId) {
    Movement::where('treasury_payment_concept_id', $paymentConceptId)
      ->where('is_paid', true)
      ->where('treasury_movement_type_id', MovementType::INCOME)
      ->exists();
  }
}