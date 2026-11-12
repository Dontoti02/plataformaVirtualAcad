<?php

namespace Modules\Tenant\Modules\Import\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportDetail extends Model
{
    protected $table = 'import_detail';

    protected $fillable = [
        'import_id',
        'is_current',
        'is_active',
        'status',
        'status',
        'progress',
        'date',
        'time_elapsed',
        'log',
        'summary',
    ];

    public $timestamps = false;

    public function import(): BelongsTo
    {
        return $this->belongsTo(Import::class);
    }
}
