<?php

namespace Modules\Tenant\Modules\Treasury\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PaymentConcept extends Model
{
    protected $table = 'treasury_payment_concept';

    protected $fillable = [
        'code',
        'name',
        'treasury_denomination_id',
        'gross_amount',
        'net_amount',
        'igv_amount',
        'max_quotas',
        'is_active',
        'can_be_paid_in_quotas',
        'include_in_enrollment'
    ];

    // cast attributes
    protected $casts = [
      'gross_amount' => 'decimal:2',
      'net_amount' => 'decimal:2',
      'igv_amount' => 'decimal:2',
      'max_quotas' => 'integer',
      'is_active' => 'boolean',
      'can_be_paid_in_quotas' => 'boolean',
      'include_in_enrollment' => 'boolean',
    ];

    public function getCreatedAtAttribute($value)
    {
      return Carbon::parse($value)->format('d-m-Y h:i A');
    }

    public function getUpdatedAtAttribute($value)
    {
      return Carbon::parse($value)->format('d-m-Y h:i A');
    }

    public function denomination(): BelongsTo
    {
        return $this->belongsTo(Denomination::class,'treasury_denomination_id');
    }


    public static function generateCode(): string
    {
        $last = self::orderBy('id', 'desc')->first();
        if ($last) {
            $lastCode = $last->code;
            $lastCode = explode('-', $lastCode);
            $lastCode = $lastCode[1];
            $lastCode = (int)$lastCode;
            $lastCode++;
            $lastCode = str_pad($lastCode, 4, '0', STR_PAD_LEFT);
            return 'PC-' . $lastCode;
        }
        return 'PC-0001';
    }
    use SoftDeletes;
}
