<?php

namespace Modules\Tenant\Modules\Classroom\Helpers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Modules\Tenant\Modules\Classroom\Models\Participant;
use Modules\Tenant\Modules\Classroom\Models\Teacher;
use Modules\Tenant\Modules\User\Enums\RolTenant;

class ClassroomHelper
{
    public static function validateOptionsRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'search'        => 'nullable|string',
            'period_id'     => 'nullable|integer|exists:period,id',
        ]);

        if ($validator->fails()) {
            throw new Exception($validator->errors()->first());
        }
    }

    public static function validateUpdateImageRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'classroom_id'  => 'required|numeric|exists:classroom,id',
            'file'          => 'required|file|mimes:jpg,jpeg,png,gif',
        ]);

        if ($validator->fails()) {
            throw new Exception($validator->errors()->first());
        }
    }

    public static function validateAccess($person_id, $classroom_id)
    {
        $is_teacher = Teacher::select()
            ->where('person_id', $person_id)
            ->where('classroom_id', $classroom_id)
            ->exists();

        $is_student = Participant::select()
            ->where('person_id', $person_id)
            ->where('classroom_id', $classroom_id)
            ->exists();

        if (!$is_teacher && !$is_student) {
            throw new Exception("No formas parte de esta clase");
        }
    }

    public static function validateASTAccess($person_id, $rol_id, $classroom_id)
    {
        $is_secretary = in_array($rol_id, [RolTenant::SECRETARY, RolTenant::ADMINISTRADOR, RolTenant::DIRECTOR_GENERAL, RolTenant::JEFE_UNIDAD_ACADEMICA, RolTenant::COORDINADOR_ACADEMICO]);

        $is_teacher = Teacher::select()
            ->where('person_id', $person_id)
            ->where('classroom_id', $classroom_id)
            ->exists();

        if (!$is_secretary && !$is_teacher) {
            throw new Exception("No tienes permisos para realizar esta acción");
        }

        if ((int) $rol_id === RolTenant::COORDINADOR_ACADEMICO) {
            \Modules\Tenant\Modules\Management\Helpers\StudyProgramHelper::validateClassroomAccess((int) $classroom_id);
        }
    }

    public static function validateTeacherAccess($person_id, $classroom_id)
    {
        $teacher = Teacher::select()
            ->where('person_id', $person_id)
            ->where('classroom_id', $classroom_id)
            ->first();

        if (!$teacher) {
            throw new Exception("No eres profesor de esta clase");
        }

        return $teacher;
    }

    public static function validateStudentAccess($person_id, $classroom_id, $isStudent = true)
    {
        $participant = Participant::select()
            ->where('person_id', $person_id)
            ->where('classroom_id', $classroom_id)
            ->first();

        if (!$participant) {
            throw new Exception($isStudent ? "No eres estudiante de esta clase" : "El estudiante no pertenece a esta clase");
        }

        return $participant;
    }

    public static function validateASTSAccess($person_id, $rol_id, $classroom_id)
    {
        $is_secretary = in_array($rol_id, [RolTenant::SECRETARY, RolTenant::ADMINISTRADOR, RolTenant::DIRECTOR_GENERAL, RolTenant::JEFE_UNIDAD_ACADEMICA, RolTenant::COORDINADOR_ACADEMICO]);

        $is_teacher = Teacher::select()
            ->where('person_id', $person_id)
            ->where('classroom_id', $classroom_id)
            ->exists();

        $is_student = Participant::select()
            ->where('person_id', $person_id)
            ->where('classroom_id', $classroom_id)
            ->exists();

        if (!$is_secretary && !$is_teacher && !$is_student) {
            throw new Exception("No formas parte de esta clase");
        }

        if ((int) $rol_id === RolTenant::COORDINADOR_ACADEMICO) {
            \Modules\Tenant\Modules\Management\Helpers\StudyProgramHelper::validateClassroomAccess((int) $classroom_id);
        }
    }

    public static function validateReportRequest($request)
    {
        $validator = Validator::make($request->all(), [
            "classroom_id"  => "required|numeric|exists:classroom,id",
        ]);

        if ($validator->fails()) {
            throw new Exception($validator->errors()->first());
        }
    }
}
