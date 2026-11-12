<?php

namespace Modules\Tenant\Modules\Treasury\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Tenant\Modules\User\Models\Person;

class PaymentConceptHistory extends Model
{
    protected $table = 'treasury_payment_concept_history';

    protected $fillable = [
        'code',
        'name',
        'treasury_payment_concept_id',
        'treasury_denomination_id',
        'person_change_id',
        'gross_amount',
        'igv_amount',
        'net_amount',
        'max_quotas',
        'is_active',
        'can_be_paid_in_quotas',
        'include_in_enrollment'
    ];

    public function denomination(): BelongsTo
    {
      return $this->belongsTo(Denomination::class,'treasury_denomination_id');
    }

    public function personChange(): BelongsTo
    {
      return $this->belongsTo(Person::class,'person_change_id');
    }

    public function paymentConcept(): BelongsTo
    {
      return $this->belongsTo(PaymentConcept::class,'treasury_payment_concept_id');
    }
}
