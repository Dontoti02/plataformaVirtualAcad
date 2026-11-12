<?php

namespace Modules\Tenant\Modules\Treasury\Adapters;

use Modules\Tenant\Modules\Treasury\Enum\PaymentConceptEnum;
use Modules\Tenant\Modules\Treasury\Models\PaymentConcept;

class PaymentConceptAdapter
{
    public static function transform(PaymentConcept $paymentConcept)
    {
        return [
            'id' => $paymentConcept->id,
            'code' => $paymentConcept->code,
            'name' => $paymentConcept->name,
            'denomination' => $paymentConcept->denomination->name,
            'denominationId' => $paymentConcept->denomination->id,
            'gross_amount' => $paymentConcept->gross_amount,
            'net_amount' => $paymentConcept->net_amount,
            'igv_amount' => $paymentConcept->igv_amount,
            'maxQuotas' => $paymentConcept->max_quotas,
            'isActive' => $paymentConcept->is_active,
            'canBePaidInQuotas' => $paymentConcept->can_be_paid_in_quotas,
            'includeInEnrollment' => $paymentConcept->include_in_enrollment,
            'createdAt' => $paymentConcept->created_at,
            'updatedAt' => $paymentConcept->updated_at,
            'isEnrollmment' => $paymentConcept->code === PaymentConceptEnum::MATRICULA_CONCEPT_CODE,
            'isPension' => $paymentConcept->code === PaymentConceptEnum::PENSIONES_CONCEPT_CODE,
        ];
    }
}