<?php

namespace Modules\Admin\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Admin\UseCases\SystemConfigurationUseCases;

class SystemConfigurationController extends Controller
{
    public function general()
    {
        return SystemConfigurationUseCases::general();
    }

    public function list()
    {
        return SystemConfigurationUseCases::list();
    }

    public function update(string $key, Request $request)
    {
        return SystemConfigurationUseCases::update($key, $request);
    }
}
