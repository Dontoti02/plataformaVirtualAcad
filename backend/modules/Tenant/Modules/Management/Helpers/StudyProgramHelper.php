<?php

namespace Modules\Tenant\Modules\Management\Helpers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Modules\Tenant\Modules\Management\Models\StudyProgram;

class StudyProgramHelper
{
    public static function list()
    {
        $study_programs = StudyProgram::select('id', 'name')
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
