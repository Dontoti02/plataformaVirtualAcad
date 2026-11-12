<?php

namespace Modules\Tenant\Modules\Management\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Tenant\Modules\Enroll\Models\Enroll;

class StudyPlan extends Model
{
    protected $table = 'study_plan';

    protected $fillable = [
        'name',
        'type',
    ];

    public function programs(): BelongsToMany
    {
        return $this->belongsToMany(StudyProgram::class);
    }

    public function enrolls(): HasMany
    {
        return $this->hasMany(Enroll::class);
    }
}
