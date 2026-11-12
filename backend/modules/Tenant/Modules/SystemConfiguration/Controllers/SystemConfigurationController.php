<?php

namespace Modules\Tenant\Modules\SystemConfiguration\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Tenant\Modules\SystemConfiguration\UseCases\SystemConfigurationUseCases;

class SystemConfigurationController extends Controller
{
    public function general()
    {
        return SystemConfigurationUseCases::general();
    }

    public function landingPage()
    {
        return SystemConfigurationUseCases::landingPage();
    }

    public function list()
    {
        return SystemConfigurationUseCases::list();
    }

    public function update(string $key, Request $request)
    {
        return SystemConfigurationUseCases::update($key, $request);
    }

    public function uploadImage(Request $request)
    {
        return SystemConfigurationUseCases::uploadImage($request);
    }

    public function deleteImage(Request $request)
    {
        return SystemConfigurationUseCases::deleteImage($request);
    }

    public function mail()
    {
        return SystemConfigurationUseCases::mail();
    }

    public function updateMail(Request $request)
    {
        return SystemConfigurationUseCases::updateMail($request);
    }

    public function testMail(Request $request)
    {
        return SystemConfigurationUseCases::testMail($request);
    }
}
