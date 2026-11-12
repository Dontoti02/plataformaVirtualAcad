<?php
namespace Modules\Tenant\Modules\Treasury\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Tenant\Modules\Management\Models\Period;
use Modules\Tenant\Modules\Treasury\Models\PaymentConcept;
use Modules\Tenant\Modules\User\Models\Person;

class Movement extends Model
{

    protected $table = 'treasury_movement';

    protected $fillable = [
        'code',
        'treasury_movement_type_id',
        'treasury_payment_concept_id',
        'period_id',
        'person_id',
        'person_registration_id',
        'amount',
        'initial_amount',
        'amount_to_divide',
        'quotas',
        'is_paid',
        'remaining_amount',
        'due_date',
        'payment_date',
        'is_exonerated',
        'exoneration_reason',
        'refund_movement_id',
        'discounts'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'initial_amount' => 'decimal:2',
        'amount_to_divide' => 'decimal:2',
        'remaining_amount' => 'decimal:2',
        'is_paid' => 'boolean',
        'is_exonerated' => 'boolean',
        'due_date' => 'datetime',
        'payment_date' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
        'refund_movement_id'=>'integer',
        'discounts'=>'decimal:2'
    ];

    protected $dates = [
        'due_date',
        'payment_date',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'person_id');
    }

    public function personRegistration(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'person_registration_id');
    }
    public function period(): BelongsTo
    {
        return $this->belongsTo(Period::class);
    }
    public function paymentConcept(): BelongsTo
    {
        return $this->belongsTo(PaymentConcept::class,   'treasury_payment_concept_id')->withTrashed();
    }
    public function movementType(): BelongsTo
    {
        return $this->belongsTo(MovementType::class, 'treasury_movement_type_id');
    }

    public function MovementDetails()
    {
        return $this->hasMany(MovementDetails::class, 'treasury_movement_id');
    }

    use SoftDeletes;

}
