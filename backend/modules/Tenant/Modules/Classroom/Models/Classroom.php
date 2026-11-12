<?php

namespace Modules\Tenant\Modules\Classroom\Models;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\Tenant\Modules\Management\Models\Course;
use Modules\Tenant\Modules\Management\Models\Period;
use Modules\Tenant\Modules\Management\Models\Section;
use Modules\Tenant\Modules\Management\Models\Shift;
use Modules\Tenant\Modules\Schedule\Models\Schedule;

class Classroom extends Model
{
    protected $table = 'classroom';

    protected $fillable = [
        'course_id',
        'period_id',
        'section_id',
        'shift_id',
        'avatar',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(Period::class);
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function teacher(): HasOne
    {
        return $this->hasOne(Teacher::class);
    }

    public function participants(): HasMany
    {
        return $this->hasMany(Participant::class);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class);
    }

    public function assistances(): HasMany
    {
        return $this->hasMany(Assistance::class);
    }

    public function contentGroups(): HasMany
    {
        return $this->hasMany(ContentGroup::class);
    }

    public function evaluationGroups(): HasMany
    {
        return $this->hasMany(EvaluationGroup::class);
    }

    public function capacities(): HasMany
    {
        return $this->hasMany(Capacity::class);
    }

    public function publications(): HasMany
    {
        return $this->hasMany(Publication::class);
    }

    public function scopeByKey($query, $key, $value)
    {
        $result = $query->where($key, $value)->first();

        if (!$result) {
            throw new Exception('Clase no encontrada');
        }

        return $result;
    }
}
