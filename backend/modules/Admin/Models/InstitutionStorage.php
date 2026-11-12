<?php

namespace Modules\Admin\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class InstitutionStorage extends Model
{
    protected $table = 'institution_storage';

    protected $fillable = [
        'id',
        'institution_id',
        'folder_name',
        'limit_space_mb',
        'used_space_mb',
        'free_space_mb',
    ];

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }
}
