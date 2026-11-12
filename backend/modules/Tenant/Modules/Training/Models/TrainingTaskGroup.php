<?php

namespace Modules\Tenant\Modules\Training\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Tenant\Modules\User\Models\Person;

class TrainingTaskGroup extends Model
{
    use SoftDeletes;

    protected $table = 'training_task_group';

    protected $fillable = [
        'training_id',
        'training_content_id',
        'person_task_register_id',
        'name',
        'score',
        'num_participants',
        'task_send',
    ];

    protected $casts = [
        'score' => 'float',
        'task_send' => 'boolean',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function training(): BelongsTo
    {
        return $this->belongsTo(Training::class, 'training_id');
    }

    public function content(): BelongsTo
    {
        return $this->belongsTo(TrainingContent::class, 'training_content_id');
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'person_task_register_id');
    }

    public function participants(): HasMany
    {
        return $this->hasMany(TrainingTaskGroupParticipant::class, 'training_task_group_id');
    }
}
