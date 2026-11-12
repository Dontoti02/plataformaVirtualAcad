<?php

namespace Modules\Admin\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Admin\UseCases\InstitutionUseCases;

class InstitutionController extends Controller
{
    public function config()
    {
        return InstitutionUseCases::config();
    }

    public function list(Request $request)
    {
        return InstitutionUseCases::list($request);
    }

    public function get(int $id)
    {
        return InstitutionUseCases::get($id);
    }

    public function create(Request $request)
    {
        return InstitutionUseCases::create($request);
    }

    public function update(int $id, Request $request)
    {
        return InstitutionUseCases::update($id, $request);
    }

    public function disable(int $id)
    {
        return InstitutionUseCases::disable($id);
    }

    public function subscription(int $id, Request $request)
    {
        return InstitutionUseCases::subscription($id, $request);
    }

    public function existSubdomain(string $subdomain,Request $request)
    {
        return InstitutionUseCases::existSubdomain($subdomain,$request);
    }

    public function createUser(Request $request)
    {
        return InstitutionUseCases::createUser($request);
    }

    public function detail(int $id)
    {
        return InstitutionUseCases::detail($id);
    }

    public function resizeStorage(Request $request)
    {
        return InstitutionUseCases::resizeStorage($request);
    }

    public function updateSubdomain(Request $request)
    {
        return InstitutionUseCases::updateSubdomain($request);
    }

    public function delete(int $id,Request $request) {
        return InstitutionUseCases::delete($id,$request);
    }
}
