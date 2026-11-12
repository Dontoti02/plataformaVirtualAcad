<?php

namespace Modules\Tenant\Modules\Classroom\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Tenant\Modules\User\Models\Person;

class Teacher extends Model
{
    protected $table = 'teacher';

    protected $fillable = [
        'person_id',
        'classroom_id',
    ];

    public $timestamps = false;

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }
}
