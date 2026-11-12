<?php

namespace Modules\Tenant\Modules\Management\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Tenant\Modules\Classroom\Models\Classroom;
use Modules\Tenant\Modules\Classroom\Models\Student;
use Modules\Tenant\Modules\Enroll\Models\Enroll;
use Modules\Tenant\Modules\Training\Models\Training;

class Period extends Model
{
    protected $table = 'period';

    protected $fillable = [
        'id',
        'name',
        'status',
        'start_date',
        'end_date',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    public function staffs(): HasMany
    {
        return $this->hasMany(Staff::class);
    }

    public function programPlans(): HasMany
    {
        return $this->hasMany(ProgramPlan::class);
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    public function classrooms(): HasMany
    {
        return $this->hasMany(Classroom::class);
    }

    public function trainings(): HasMany
    {
        return $this->hasMany(Training::class);
    }

    public function scopeByKey($query, $key, $value)
    {
        $result = $query->where($key, $value)->first();

        if (!$result) {
            throw new \Exception('Periodo no encontrado');
        }

        return $result;
    }

    public function enrolls(): HasMany
    {
        return $this->hasMany(Enroll::class);
    }
}
