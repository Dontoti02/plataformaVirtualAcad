<?php

namespace Modules\Tenant\Modules\Management\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Tenant\Modules\Management\UseCases\PeriodUseCases;

class PeriodController extends Controller
{
    public function list()
    {
        return PeriodUseCases::list();
    }

    public function current()
    {
        return PeriodUseCases::current();
    }

    public function disable(int $id)
    {
        return PeriodUseCases::disable($id);
    }

    public function filtersClassrooms()
    {
        return PeriodUseCases::filtersClassrooms();
    }

    public function listClassrooms(Request $request)
    {
        return PeriodUseCases::listClassrooms($request);
    }

    public function updateDate(Request $request)
    {
        return PeriodUseCases::updateDate($request);
    }
}
