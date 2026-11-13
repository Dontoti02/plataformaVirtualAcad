<?php

namespace Modules\Tenant\Modules\FailedStudents\Helpers;

use Exception;
use Illuminate\Http\Request;
use Modules\Shared\Helpers\SessionManager;
use Modules\Tenant\Modules\User\Enums\RolTenant;
use Modules\Tenant\Modules\User\Models\User;

class FailedStudentsHelper
{
    public static function validateAccess()
    {
        $session = SessionManager::get();
        $user = $session ? User::find($session->id) : null;

        if (!$user || !in_array($user->rol_id, [
            RolTenant::COORDINADOR_ACADEMICO,
            RolTenant::JEFE_UNIDAD_ACADEMICA,
            RolTenant::DIRECTOR_GENERAL,
            RolTenant::ADMINISTRADOR,
            RolTenant::SECRETARY,
        ])) {
            throw new Exception("No tienes permisos para acceder al reporte de estudiantes no aprobados");
        }

        return $user;
    }

    public static function validateFilterRequest(Request $request)
    {
        $request->validate([
            'period_id' => 'nullable|numeric|exists:period,id',
            'study_program_id' => 'nullable|numeric|exists:study_program,id',
            'cycle_id' => 'nullable|numeric|exists:cycle,id',
            'classroom_id' => 'nullable|numeric|exists:classroom,id',
            'search' => 'nullable|string|max:100',
        ]);
    }
}
