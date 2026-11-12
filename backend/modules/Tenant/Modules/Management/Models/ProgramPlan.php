<?php

namespace Modules\Tenant\Modules\Management\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProgramPlan extends Model
{
    protected $table = 'program_plan';

    protected $fillable = [
        'study_program_id',
        'study_plan_id',
    ];

    public $timestamps = false;

    public function program(): BelongsTo
    {
        return $this->belongsTo(StudyProgram::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(StudyPlan::class);
    }
}
