<?php

namespace Modules\Tenant\Modules\User\Controllers;

use App\Http\Controllers\Controller;
use Modules\Tenant\Modules\User\UseCases\ProfileUseCases;

class ProfileController extends Controller
{
    public function get()
    {
        return ProfileUseCases::get();
    }
}
