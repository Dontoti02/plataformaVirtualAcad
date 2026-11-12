<?php

namespace Modules\Tenant\Modules\Management\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Tenant\Modules\Management\UseCases\RegistrationUseCases;

class RegistrationController extends Controller
{
    public function filters()
    {
        return RegistrationUseCases::filters();
    }

    public function listStudents(Request $request)
    {
        return RegistrationUseCases::listStudents($request);
    }

    public function list(Request $request)
    {
        return RegistrationUseCases::list($request);
    }
}
