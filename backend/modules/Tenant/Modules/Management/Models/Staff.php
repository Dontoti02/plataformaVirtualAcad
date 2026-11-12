<?php

namespace Modules\Tenant\Modules\Management\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Tenant\Modules\Classroom\Models\Classroom;
use Modules\Tenant\Modules\User\Models\Person;

class Staff extends Model
{
    protected $table = 'staff';

    protected $fillable = [
        'period_id',
        'person_id',
        'type',
        'registration_date',
        'working_condition',
    ];

    public function period(): BelongsTo
    {
        return $this->belongsTo(Period::class);
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function classrooms(): HasMany
    {
        return $this->hasMany(Classroom::class, 'teacher_id');
    }

    public function scopeByKey($query, $key, $value)
    {
        $result = $query->where($key, $value)->first();

        if (!$result) {
            throw new \Exception('Personal no encontrado');
        }

        return $result;
    }
}
