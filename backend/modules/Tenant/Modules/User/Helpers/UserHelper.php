<?php

namespace Modules\Tenant\Modules\User\Helpers;

use Exception;
use Illuminate\Http\Request;
use Modules\Shared\Helpers\SessionManager;
use Illuminate\Support\Facades\Validator;
use Modules\Tenant\Modules\User\Enums\RolTenant;
use Modules\Tenant\Modules\User\Models\User;

class UserHelper
{
    public static function validateListRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "page"              => "required|integer|gt:0",
            "size"              => "required|integer|gt:0",
            "rol_ids"           => "required|array",
            "rol_ids.*"         => "required|numeric|exists:rol,id",
            "search"            => "nullable|string",
        ]);

        if ($validator->fails()) {
            throw new Exception($validator->errors()->first());
        }
    }

    public static function validateAccess(int $id)
    {
        $session = SessionManager::get();
        $user_login = User::find($session->id);

        $is_secretary = $user_login->rol_id === RolTenant::SECRETARY;
        $is_admin = in_array($user_login->rol_id, [
            RolTenant::ADMINISTRADOR,
            RolTenant::DIRECTOR_GENERAL,
            RolTenant::JEFE_UNIDAD_ACADEMICA,
            RolTenant::COORDINADOR_ACADEMICO,
        ]);

        $user = User::byKey('id', $id);

        if (!$is_secretary && !$is_admin && $user_login->id !== $user->id) {
            throw new Exception("No tienes permisos para realizar esta acción");
        }

        return $user;
    }

    public static function validateCreateRequest(Request $request)
    {
        $roles = implode(',', [
            RolTenant::ADMINISTRADOR,
            RolTenant::TRAINING_ADMINISTRADOR,
            RolTenant::DIRECTOR_GENERAL,
            RolTenant::JEFE_UNIDAD_ACADEMICA,
            RolTenant::COORDINADOR_ACADEMICO,
        ]);

        $rolIds = array_map('intval', is_array($request->rol_ids) ? $request->rol_ids : []);
        $requiresStudyProgram = in_array(RolTenant::COORDINADOR_ACADEMICO, $rolIds, true);

        $validator = Validator::make($request->all(), [
            "document_type"       => "required|string|exists:person,document_type",
            "document_number"     => "required|string|size:8|unique:person,document_number",
            "names"               => "required|string",
            "phone"               => "required|string|size:9",
            "email"               => "required|email|unique:user,email",
            "rol_ids"             => "required|array",
            "rol_ids.*"           => "required|numeric|exists:rol,id|in:" . $roles,
            "study_program_ids"   => $requiresStudyProgram ? "required|array|min:1" : "nullable|array",
            "study_program_ids.*" => "required|numeric|exists:study_program,id",
        ]);

        if ($validator->fails()) {
            throw new Exception($validator->errors()->first());
        }
    }

    public static function validateUpdateRequest(Request $request, User $user, $is_admin)
    {
        $exceptIdPerson = $user->person_id;
        $exceptIdUser = $user->id;
        $required = $is_admin ? 'required' : 'nullable';
        $isCoordinator = $user->roles()
            ->where('rol.id', RolTenant::COORDINADOR_ACADEMICO)
            ->exists();

        $validator = Validator::make($request->all(), [
            "document_type"       => "$required|string|exists:person,document_type",
            "document_number"     => "$required|string|size:8|unique:person,document_number,$exceptIdPerson",
            "names"               => "$required|string",
            "phone"               => "required|string|size:9",
            "email"               => "required|email|unique:user,email,$exceptIdUser",
            "study_program_ids"   => ($isCoordinator && $request->exists('study_program_ids'))
                ? "required|array|min:1"
                : "nullable|array",
            "study_program_ids.*" => "required|numeric|exists:study_program,id",
        ]);

        if ($validator->fails()) {
            throw new Exception($validator->errors()->first());
        }
    }

    public static function validateChangePasswordRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "password" => "required|string|min:8",
        ]);

        if ($validator->fails()) {
            throw new Exception($validator->errors()->first());
        }
    }

    public static function validateChangePhotoRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:jpg,jpeg,png,gif',
        ]);

        if ($validator->fails()) {
            throw new Exception($validator->errors()->first());
        }
    }

    public static function validateDisableOrDeleteAccess(int $id)
    {
        $session = SessionManager::get();

        $user_login = User::find($session->id);

        $user = User::byKey('id', $id);

        if (!in_array($user_login->rol_id, [
            RolTenant::SECRETARY,
            RolTenant::ADMINISTRADOR,
            RolTenant::DIRECTOR_GENERAL,
            RolTenant::JEFE_UNIDAD_ACADEMICA,
            RolTenant::COORDINADOR_ACADEMICO,
        ])) {
            throw new Exception("No tienes permisos para realizar esta acción");
        }

        if ($user_login->id == $user->id) {
            throw new Exception("No puedes desactivar o eliminar tu propio usuario");
        }

        return $user;
    }
}
