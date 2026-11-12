<?php

namespace Modules\Admin\Models;

use Exception;
use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;

class Tenant extends BaseTenant implements TenantWithDatabase
{
    use HasDatabase, HasDomains;

    protected $table = 'tenant';

    public static function getCustomColumns(): array
    {
        return [
            'tenancy_db_name',
        ];
    }

    public function getIncrementing()
    {
        return true;
    }

    public function scopeByKey($query, $key, $value)
    {
        $result = $query->where($key, $value)->first();

        if (!$result) {
            throw new Exception('Tenant no encontrado');
        }

        return $result;
    }
}
