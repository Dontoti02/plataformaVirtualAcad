<?php

namespace Modules\Tenant\Modules\Management\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Tenant\Modules\Classroom\Models\Classroom;
use Modules\Tenant\Modules\Enroll\Models\Enroll;

class Shift extends Model
{
    protected $table = 'shift';

    protected $fillable = [
        'name',
    ];

    public function classrooms(): HasMany
    {
        return $this->hasMany(Classroom::class);
    }

    public function enrolls(): HasMany
    {
        return $this->hasMany(Enroll::class);
    }
}
