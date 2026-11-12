<?php

namespace Modules\Shared\Models;

use Illuminate\Database\Eloquent\Model;

class Ubigeo extends Model
{
    protected $table = 'ubigeo';

    protected $fillable = [
        'inei',
        'reniec',
        'department',
        'province',
        'district',
    ];
}
