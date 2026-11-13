<?php

namespace Modules\Tenant\Modules\FailedStudents\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Tenant\Modules\FailedStudents\UseCases\FailedStudentsUseCases;

class FailedStudentsController extends Controller
{
    public function filters(Request $request)
    {
        return FailedStudentsUseCases::filters($request);
    }

    public function list(Request $request)
    {
        return FailedStudentsUseCases::list($request);
    }
}
