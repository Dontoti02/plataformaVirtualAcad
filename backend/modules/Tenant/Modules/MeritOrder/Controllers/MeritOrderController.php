<?php

namespace Modules\Tenant\Modules\MeritOrder\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Tenant\Modules\MeritOrder\UseCases\MeritOrderUseCases;

class MeritOrderController extends Controller
{
    public function filters()
    {
        return MeritOrderUseCases::filters();
    }

    public function list(Request $request)
    {
        return MeritOrderUseCases::list($request);
    }
}
