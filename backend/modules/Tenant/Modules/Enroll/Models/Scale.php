<?php
namespace Modules\Tenant\Modules\Enroll\Models;
use Illuminate\Database\Eloquent\Model;

class Scale extends Model
{
    protected $table = 'scale';

    protected $fillable = [
        'name',
        'scale_amount',
    ];

    protected $casts = [
        'scale_amount' => 'decimal:2',
    ];
    
    public function enrolls()
    {
        return $this->hasMany(Enroll::class);
    }
    public $timestamps = false;
    
}
?>