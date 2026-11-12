<?php

namespace Modules\Tenant\Modules\Management\Repositories;

use Illuminate\Http\Request;
use Modules\Shared\Helpers\SessionManager;
use Modules\Tenant\Modules\Management\Helpers\StudyProgramHelper;
use Modules\Tenant\Modules\Management\Models\Cycle;
use Modules\Tenant\Modules\User\Enums\RolTenant;
use Modules\Tenant\Modules\User\Models\User;

class StudyProgramRepository
{
    public static function filters()
    {
        $study_programs = StudyProgramHelper::list();

        $result = [
            'study_programs' => $study_programs,
        ];

        return $result;
    }

    public static function list(Request $request)
    {
        $session = SessionManager::get();

        $user = User::find($session->id);
        $is_student = $user->rol_id === RolTenant::STUDENT;

        StudyProgramHelper::validateListRequest($request, $is_student);

        $study_program_id = $is_student
            ? $user->person->students->last()->study_program_id
            : $request->study_program_id;

        $cycles = Cycle::select('id', 'name')
            ->orderBy('name', 'asc')
            ->get()
            ->each(function ($item) use ($study_program_id) {
                $item->courses = $item->courses()
                    ->select('id', 'name')
                    ->where('study_program_id', $study_program_id)
                    ->orderBy('name', 'asc')
                    ->distinct()
                    ->get();
            });

        return $cycles;
    }
}
