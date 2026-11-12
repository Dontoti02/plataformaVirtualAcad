<?php

namespace Modules\Tenant\Modules\Training\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class TrainingTaskGroupParticipant extends Model
{
    use SoftDeletes;

    protected $table = 'training_task_group_participant';

    protected $fillable = [
        'training_task_group_id',
        'training_participant_id',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function taskGroup(): BelongsTo
    {
        return $this->belongsTo(TrainingTaskGroup::class, 'training_task_group_id');
    }

    public function participant(): BelongsTo
    {
        return $this->belongsTo(TrainingParticipant::class, 'training_participant_id');
    }
}
