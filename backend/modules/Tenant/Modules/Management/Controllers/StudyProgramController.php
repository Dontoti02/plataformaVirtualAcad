<?php

namespace Modules\Tenant\Modules\Management\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Tenant\Modules\Management\UseCases\StudyProgramUseCases;

class StudyProgramController extends Controller
{
    public function filters()
    {
        return StudyProgramUseCases::filters();
    }

    public function list(Request $request)
    {
        return StudyProgramUseCases::list($request);
    }
}
