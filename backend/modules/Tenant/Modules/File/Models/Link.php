<?php

namespace Modules\Tenant\Modules\File\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Link extends Model
{
    protected $table = 'link';

    protected $fillable = [
        'linkable_id',
        'linkable_type',
        'url',
    ];

    public function linkable(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeByKey($query, $key, $value)
    {
        $result = $query->where($key, $value)->first();

        if (!$result) {
            throw new \Exception('Link no encontrado');
        }

        return $result;
    }
}
