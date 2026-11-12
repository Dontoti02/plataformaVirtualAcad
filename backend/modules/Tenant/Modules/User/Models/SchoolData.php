<?php
namespace Modules\Tenant\Modules\User\Models;
use Illuminate\Database\Eloquent\Model;

class SchoolData extends Model
{
    protected $table = 'school_data';

    protected $fillable = [
        'modular_code',
        'name',
        'start_date',
        'end_date',
        'type',
        'category',
        'CEVA_certificate',
        'condition',
        'observations',
        'photo',
        'academic_validation',
        'person_id'
    ];
    public $timestamps = false;
    public function person()
    {
        return $this->belongsTo(Person::class);
    }
}