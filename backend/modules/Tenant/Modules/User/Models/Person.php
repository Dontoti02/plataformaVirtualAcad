<?php

namespace Modules\Tenant\Modules\User\Models;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Tenant\Modules\Classroom\Models\Answer;
use Modules\Tenant\Modules\Classroom\Models\Assistance;
use Modules\Tenant\Modules\Classroom\Models\Comment;
use Modules\Tenant\Modules\Classroom\Models\Participant;
use Modules\Tenant\Modules\Classroom\Models\Publication;
use Modules\Tenant\Modules\Classroom\Models\Student;
use Modules\Tenant\Modules\Classroom\Models\Teacher;
use Modules\Tenant\Modules\Management\Models\Staff;
use Modules\Tenant\Modules\User\Models\AditionalData;
use Modules\Tenant\Modules\User\Models\SchoolData;
use Modules\Tenant\Modules\User\Models\Family;
use Modules\Tenant\Modules\Enroll\Models\Enroll;

class Person extends Model
{
    use SoftDeletes;

    protected $table = 'person';

    protected $fillable = [
        'document_type',
        'document_number',
        'names',
        'phone',
        'email',
        'sex',
        'birth_date',
        'native_language',
    ];

    public function user(): HasOne
    {
        return $this->hasOne(User::class);
    }

    public function staffs(): HasMany
    {
        return $this->hasMany(Staff::class);
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    public function teachers(): HasMany
    {
        return $this->hasMany(Teacher::class);
    }

    public function participants(): HasMany
    {
        return $this->hasMany(Participant::class);
    }

    public function assistances(): HasMany
    {
        return $this->hasMany(Assistance::class);
    }

    public function publications(): HasMany
    {
        return $this->hasMany(Publication::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(Answer::class);
    }

    public function aditionalData(): HasOne
    {
        return $this->hasOne(AditionalData::class);
    }

    public function schoolData(): HasOne
    {
        return $this->hasOne(SchoolData::class);
    }

    public function family(): HasOne
    {
        return $this->hasOne(Family::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enroll::class);
    }

    public function scopeByKey($query, $key, $value)
    {
        $result = $query->where($key, $value)->first();

        if (!$result) {
            throw new Exception('Persona no encontrada');
        }

        return $result;
    }
}
