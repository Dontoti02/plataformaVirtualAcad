<?php

namespace Modules\Tenant\Modules\User\Models;

use Exception;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Modules\Shared\Helpers\SessionManager;
use Modules\Tenant\Modules\JobOpportunities\Shared\Domain\Models\JobCompany;
use Modules\Tenant\Modules\JobOpportunities\Shared\Domain\Models\JobOfferCv;

class User extends Authenticatable implements JWTSubject
{
    use SoftDeletes;

    protected $table = 'user';

    protected $fillable = [
        'person_id',
        'rol_id',
        'email',
        'password',
        'remember_token',
        'reset_password_token',
        'default_user',
        'is_active',
        'last_login',
        'avatar',
        'attempts',
        'last_attempt',
        'company_id',
    ];

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Rol::class, 'rol_user');
    }

    public function hasRole($roleId): bool
    {
        return $this->roles()
            ->where('rol.id', $roleId)
            ->exists();
    }

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }

    public function scopeByKey($query, $key, $value)
    {
        $result = $query->where($key, $value)->first();

        if (!$result) {
            throw new Exception('Usuario no encontrado');
        }

        return $result;
    }

    public function scopeAuthenticated($query, $roles = [])
    {
        $session = SessionManager::get();

        $result = $query->find($session->id);

        if (!$result) {
            throw new Exception('Usuario no encontrado');
        }

        if (!is_array($roles)) {
            $roles = [$roles];
        }

        if (!empty($roles) && !$result->roles->whereIn('id', $roles)->count()) {
            throw new Exception('Usuario no autorizado');
        }

        return $result;
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(JobCompany::class, 'company_id');
    }

    public function cvs()
    {
        return $this->hasMany(JobOfferCv::class);
    }
}
