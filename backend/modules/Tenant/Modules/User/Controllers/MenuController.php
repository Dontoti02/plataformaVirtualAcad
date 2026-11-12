<?php

namespace Modules\Tenant\Modules\User\Controllers;

use App\Http\Controllers\Controller;
use Modules\Tenant\Modules\User\UseCases\MenuUseCases;

class MenuController extends Controller
{
    public function get()
    {
        return MenuUseCases::get();
    }
}
