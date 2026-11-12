<?php

namespace Modules\Tenant\Modules\Treasury\Models;

use Illuminate\Database\Eloquent\Model;

class Denomination extends Model
{
    protected $table = 'treasury_denomination';

    protected $fillable = [
        'name',
        'description'
    ];
}
