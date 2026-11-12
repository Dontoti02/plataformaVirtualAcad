<?php

namespace Modules\Tenant\Modules\File\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class File extends Model
{
    protected $table = 'file';

    protected $fillable = [
        'fileable_id',
        'fileable_type',
        'name',
        'url',
        'metadata',
    ];

    public function fileable(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeByKey($query, $key, $value)
    {
        $result = $query->where($key, $value)->first();

        if (!$result) {
            throw new \Exception('Archivo no encontrado');
        }

        return $result;
    }
}
