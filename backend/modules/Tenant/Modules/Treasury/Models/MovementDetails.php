<?php

namespace Modules\Tenant\Modules\Treasury\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Tenant\Modules\Treasury\Models\PaymentConcept;
use Modules\Tenant\Modules\User\Models\Person;

class MovementDetails extends Model
{
    
    protected $table = 'treasury_movement_detail';
    
    protected $fillable = [
        'treasury_movement_id',
        'treasury_payment_concept_id',
        'person_registration_payment_id',
        'person_created_schedule_by',
        'amount',
        'is_paid',
        'due_date',
        'emission_date',
        'payment_date',
        'movement_voucher'
    ];
    
    protected $casts = [
        'amount' => 'decimal:2',
        'is_paid' => 'boolean',
        'due_date' => 'datetime',
        'emission_date' => 'datetime',
        'payment_date' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];
    
    protected $dates = [
        'due_date',
        'emission_date',
        'payment_date',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    function movement()
    {
        return $this->belongsTo(Movement::class, 'treasury_movement_id');
    }

    function paymentConcept()
    {
        return $this->belongsTo(PaymentConcept::class, 'treasury_payment_concept_id');
    }

    function personRegistrationPayment()
    {
        return $this->belongsTo(Person::class, 'person_registration_payment_id');
    }

    function personCreatedScheduleBy()
    {
        return $this->belongsTo(Person::class, 'person_created_schedule_by');
    }
    use SoftDeletes;
}