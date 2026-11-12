<?php

namespace Modules\Tenant\Modules\Classroom\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Tenant\Modules\Classroom\UseCases\AssistanceUseCases;

class AssistanceController extends Controller
{
    public function dates(int $classroom_id)
    {
        return AssistanceUseCases::dates($classroom_id);
    }

    public function list(Request $request)
    {
        return AssistanceUseCases::list($request);
    }

    public function create(Request $request)
    {
        return AssistanceUseCases::create($request);
    }

    public function mark(int $id, Request $request)
    {
        return AssistanceUseCases::mark($id, $request);
    }
    public function deleteMultiple(Request $request)
    {
        return AssistanceUseCases::deleteMultiple($request);
    }
}
