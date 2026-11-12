<?php

namespace Modules\Tenant\Modules\Management\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Tenant\Modules\Management\UseCases\HistoryUseCases;

class HistoryController extends Controller
{
    public function filters()
    {
        return HistoryUseCases::filters();
    }

    public function listStudents(Request $request)
    {
        return HistoryUseCases::listStudents($request);
    }

    public function list(Request $request)
    {
        return HistoryUseCases::list($request);
    }
}
