<?php

namespace Modules\Tenant\Modules\Import\Models;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Import extends Model
{
    protected $table = 'import';

    protected $fillable = [
        'key',
        'title',
        'attributes',
    ];

    public $timestamps = false;

    public function details(): HasMany
    {
        return $this->hasMany(ImportDetail::class);
    }

    public function scopeByKey($query, $key, $value)
    {
        $result = $query->where($key, $value)->first();

        if (!$result) {
            throw new Exception('Importación no encontrada');
        }

        return $result;
    }
}
