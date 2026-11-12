<?php
namespace Modules\Tenant\Modules\User\Models;
use Illuminate\Database\Eloquent\Model;


class Family extends Model
{
    protected $table = 'family';

    protected $fillable = [
        'document_type',
        'document_number',
        'full_names',
        'phone',
        'cell_phone',
        'email',
        'address',
        'occupation',
        'relationship',
        'person_id'
    ];
    public $timestamps = false;
    public function person()
    {
        return $this->belongsTo(Person::class);
    }
}
?>