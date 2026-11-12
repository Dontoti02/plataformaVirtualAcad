<?php

namespace Modules\Tenant\Modules\Classroom\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Tenant\Modules\Classroom\UseCases\NoteUseCases;

class NoteController extends Controller
{
    public function list(Request $request)
    {
        return NoteUseCases::list($request);
    }

    public function report(Request $request)
    {
        return NoteUseCases::report($request);
    }

    public function saveRecovery(Request $request)
    {
        return NoteUseCases::saveRecovery($request);
    }
}
