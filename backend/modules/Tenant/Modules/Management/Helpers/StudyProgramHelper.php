<?php

namespace Modules\Tenant\Modules\Management\Helpers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Modules\Tenant\Modules\Management\Models\StudyProgram;

class StudyProgramHelper
{
    public static function assignedStudyProgramIds(?\Modules\Tenant\Modules\User\Models\User $user = null): ?array
    {
        if ($user === null) {
            $session = \Modules\Shared\Helpers\SessionManager::get();
            $user = ($session && isset($session->id))
                ? \Modules\Tenant\Modules\User\Models\User::find($session->id)
                : null;
        }

        if (!$user || (int) $user->rol_id !== \Modules\Tenant\Modules\User\Enums\RolTenant::COORDINADOR_ACADEMICO) {
            return null;
        }

        return \Illuminate\Support\Facades\DB::table('user_study_program')
            ->where('user_id', $user->id)
            ->pluck('study_program_id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }

    public static function scopeQueryByStudyProgram($query, string $column = 'study_program.id', ?\Modules\Tenant\Modules\User\Models\User $user = null)
    {
        $programIds = self::assignedStudyProgramIds($user);
        if ($programIds === null) {
            return $query;
        }

        if (empty($programIds)) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereIn($column, $programIds);
    }

    public static function validateStudyProgramAccess(?int $studyProgramId, ?\Modules\Tenant\Modules\User\Models\User $user = null): void
    {
        $programIds = self::assignedStudyProgramIds($user);
        if ($programIds === null) {
            return;
        }

        if (!$studyProgramId || !in_array((int) $studyProgramId, $programIds, true)) {
            throw new \Exception('No tienes permisos para acceder a este programa de estudios');
        }
    }

    public static function validateClassroomAccess(int $classroomId, ?\Modules\Tenant\Modules\User\Models\User $user = null): void
    {
        $programIds = self::assignedStudyProgramIds($user);
        if ($programIds === null) {
            return;
        }

        $studyProgramId = \Illuminate\Support\Facades\DB::table('classroom')
            ->join('course', 'classroom.course_id', '=', 'course.id')
            ->where('classroom.id', $classroomId)
            ->value('course.study_program_id');

        if (!$studyProgramId || !in_array((int) $studyProgramId, $programIds, true)) {
            throw new \Exception('No tienes permisos para acceder a esta clase');
        }
    }

    public static function validateStudentPersonAccess(int $personId, ?\Modules\Tenant\Modules\User\Models\User $user = null): void
    {
        $programIds = self::assignedStudyProgramIds($user);
        if ($programIds === null) {
            return;
        }

        if (empty($programIds)) {
            throw new \Exception('No tienes permisos para acceder a este estudiante');
        }

        $hasStudentAccess = \Illuminate\Support\Facades\DB::table('student')
            ->where('person_id', $personId)
            ->whereIn('study_program_id', $programIds)
            ->exists();

        if (!$hasStudentAccess) {
            $hasEnrollAccess = \Illuminate\Support\Facades\DB::table('enrollment')
                ->where('person_id', $personId)
                ->whereIn('study_program_id', $programIds)
                ->exists();

            if (!$hasEnrollAccess) {
                throw new \Exception('No tienes permisos para acceder a este estudiante');
            }
        }
    }

    public static function list()
    {
        $query = StudyProgram::select('id', 'name');
        self::scopeQueryByStudyProgram($query, 'study_program.id');

        $study_programs = $query
            ->orderBy('name', 'asc')
            ->get();

        return $study_programs;
    }

    public static function validateListRequest(Request $request, $is_student)
    {
        $required = $is_student ? 'nullable' : 'required';

        $validator = Validator::make($request->all(), [
            "study_program_id" => "$required|numeric|exists:study_program,id",
        ]);

        if ($validator->fails()) {
            throw new Exception($validator->errors()->first());
        }
    }
}
