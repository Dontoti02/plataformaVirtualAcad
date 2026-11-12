<?php

namespace Modules\Tenant\Modules\User\Helpers;

use Exception;
use Modules\Shared\Helpers\SessionManager;
use Modules\Tenant\Modules\User\Enums\RolTenant;
use Modules\Tenant\Modules\User\Models\User;

class RolHelper
{
    public static function validateSecretaryOrAdminAccess()
    {
        $session = SessionManager::get();

        $user = User::find($session->id);

        if (!in_array($user->rol_id, [
            RolTenant::SECRETARY,
            RolTenant::ADMINISTRADOR,
            RolTenant::DIRECTOR_GENERAL,
            RolTenant::JEFE_UNIDAD_ACADEMICA,
            RolTenant::COORDINADOR_ACADEMICO,
        ])) {
            throw new Exception("No tienes permisos para realizar esta acción");
        }

        return $user;
    }

    public static function validateSecretaryAccess()
    {
        $session = SessionManager::get();

        $user = User::find($session->id);

        if ($user->rol_id != RolTenant::SECRETARY) {
            throw new Exception("No tienes permisos para realizar esta acción");
        }

        return $user;
    }

    public static function validateTeacherAccess()
    {
        $session = SessionManager::get();

        $user = User::find($session->id);

        if ($user->rol_id != RolTenant::TEACHER) {
            throw new Exception("No tienes permisos para realizar esta acción");
        }

        return $user;
    }

    public static function validateStudentAccess()
    {
        $session = SessionManager::get();

        $user = User::find($session->id);

        if ($user->rol_id != RolTenant::STUDENT) {
            throw new Exception("No tienes permisos para realizar esta acción");
        }

        return $user;
    }

    public static function validateAdminAccess()
    {
        $session = SessionManager::get();

        $user = User::find($session->id);

        if (!in_array($user->rol_id, [
            RolTenant::ADMINISTRADOR,
            RolTenant::DIRECTOR_GENERAL,
            RolTenant::JEFE_UNIDAD_ACADEMICA,
            RolTenant::COORDINADOR_ACADEMICO,
        ])) {
            throw new Exception("No tienes permisos para realizar esta acción");
        }

        return $user;
    }
}
