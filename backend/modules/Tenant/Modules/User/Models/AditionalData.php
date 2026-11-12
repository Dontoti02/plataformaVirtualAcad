<?php
namespace Modules\Tenant\Modules\User\Models;

use Illuminate\Database\Eloquent\Model;

class AditionalData extends Model
{
    protected $table = 'person_aditional_data';

    protected $fillable = [
        'permanent_address',
        'current_address',
        'country',
        'department',
        'province',
        'district',
        'civil_status',
        'cell_phone',
        'person_id'
    ];

    public $timestamps = false;

    public function person()
    {
        return $this->belongsTo(Person::class, 'person_id');
    }
}