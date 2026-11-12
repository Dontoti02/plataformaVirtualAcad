<?php

namespace Modules\Tenant\Modules\Home\Controllers;

use App\Http\Controllers\Controller;
use Modules\Tenant\Modules\Home\UseCases\HomeUseCases;

class HomeController extends Controller
{
    public function home()
    {
        return HomeUseCases::home();
    }
}
