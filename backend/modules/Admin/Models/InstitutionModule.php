<?php

namespace Modules\Admin\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstitutionModule extends Model
{
    protected $table = 'institution_module';

    protected $fillable = [
        'id',
        'name',
        'module_key',
        'is_active',
        'start_date',
        'end_date',
        'settings',
        'institution_id',
    ];

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }
}
