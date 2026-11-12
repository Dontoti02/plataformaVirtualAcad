<?php

namespace Modules\Tenant\Modules\Classroom\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Modules\Tenant\Modules\User\Models\Person;

class Comment extends Model
{
    protected $table = 'comment';

    protected $fillable = [
        'commentable_id',
        'commentable_type',
        'person_id',
        'value',
    ];

    public function commentable(): MorphTo
    {
        return $this->morphTo();
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }
}
