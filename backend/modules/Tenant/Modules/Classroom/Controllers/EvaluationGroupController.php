<?php

namespace Modules\Tenant\Modules\Classroom\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Tenant\Modules\Classroom\UseCases\EvaluationGroupUseCases;

class EvaluationGroupController extends Controller
{
    public function list(int $classroom_id)
    {
        return EvaluationGroupUseCases::list($classroom_id);
    }

    public function set(Request $request)
    {
        return EvaluationGroupUseCases::set($request);
    }
}
