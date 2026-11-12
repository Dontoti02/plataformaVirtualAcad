<?php

namespace Modules\Admin\Models;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Rol extends Model
{
    protected $table = 'rol';

    protected $fillable = [
        'name'
    ];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'rol_user');
    }

    public function options(): BelongsToMany
    {
        return $this->belongsToMany(Option::class, 'rol_option');
    }

    public function scopeByKey($query, $key, $value)
    {
        $result = $query->where($key, $value)->first();

        if (!$result) {
            throw new Exception('Rol no encontrado');
        }

        return $result;
    }
}
