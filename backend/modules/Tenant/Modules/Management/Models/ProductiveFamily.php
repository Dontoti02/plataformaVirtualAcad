<?php

namespace Modules\Tenant\Modules\Management\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductiveFamily extends Model
{
    protected $table = 'productive_family';

    protected $fillable = [
        'name',
    ];

    public function studyPrograms(): HasMany
    {
        return $this->hasMany(StudyProgram::class);
    }
}
