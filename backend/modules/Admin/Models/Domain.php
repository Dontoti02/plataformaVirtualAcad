<?php

namespace Modules\Admin\Models;

use Exception;
use Stancl\Tenancy\Database\Models\Domain as BaseDomain;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Domain extends BaseDomain
{
    protected $connection = 'central';

    protected $table = 'domain';

    protected $fillable = [
        'tenant_id',
        'domain',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function institution(): HasOne
    {
        return $this->hasOne(Institution::class);
    }

    public function scopeByKey($query, $key, $value)
    {
        $result = $query->where($key, $value)->first();

        if (!$result) {
            throw new Exception('Dominio no encontrado');
        }

        return $result;
    }
}
