<?php

namespace Modules\Tenant\Modules\Classroom\Models;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Indicator extends Model
{
    protected $table = 'indicator';

    protected $fillable = [
        'capacity_id',
        'name',
        'order',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $casts = [
        'order' => 'integer',
    ];

    public function capacity(): BelongsTo
    {
        return $this->belongsTo(Capacity::class);
    }

    public function contents(): HasMany
    {
        return $this->hasMany(Content::class);
    }

    public function studentIndicators(): HasMany
    {
        return $this->hasMany(StudentIndicator::class);
    }

    public function scopeByKey($query, $key, $value)
    {
        $result = $query->where($key, $value)->first();

        if (!$result) {
            throw new Exception('Indicador no encontrado');
        }

        return $result;
    }
}
