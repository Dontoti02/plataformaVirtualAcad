<?php

namespace Modules\Tenant\Modules\User\Repositories;

use Exception;
use Modules\Shared\Helpers\SessionManager;
use Modules\Tenant\Modules\User\Models\Rol;
use Modules\Tenant\Modules\User\Models\User;

class RolRepository
{
    public static function list()
    {
        $roles = Rol::select()
            ->get();

        return $roles;
    }

    public static function change(int $id)
    {
        $session = SessionManager::get();

        $user = User::find($session->id);
        $rol = Rol::byKey('id', $id);

        $roles = $user->roles()->pluck('rol.id')->toArray();

        if (!in_array($rol->id, $roles)) {
            throw new Exception("No tienes el rol de $rol->name");
        }

        $user->update([
            'rol_id' => $rol->id
        ]);

        return $rol->id;
    }
}
