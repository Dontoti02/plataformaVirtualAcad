<?php

namespace Modules\Tenant\Modules\Management\Models;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Tenant\Modules\Classroom\Models\Student;
use Modules\Tenant\Modules\Enroll\Models\Enroll;

class StudyProgram extends Model
{
    protected $table = 'study_program';

    protected $fillable = [
        'id',
        'productive_family_id',
        'name',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    public function productiveFamily(): BelongsTo
    {
        return $this->belongsTo(ProductiveFamily::class);
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    public function courses(): HasMany
    {
        return $this->hasMany(Course::class);
    }

    public function plans(): BelongsToMany
    {
        return $this->belongsToMany(StudyPlan::class);
    }

    public function scopeByKey($query, $key, $value)
    {
        $result = $query->where($key, $value)->first();

        if (!$result) {
            throw new Exception('Program de estudios no encontrado');
        }

        return $result;
    }

    public function enrolls(): HasMany
    {
        return $this->hasMany(Enroll::class);
    }
}
