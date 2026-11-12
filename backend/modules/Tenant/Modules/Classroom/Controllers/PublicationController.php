<?php

namespace Modules\Tenant\Modules\Classroom\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Tenant\Modules\Classroom\UseCases\PublicationUseCases;

class PublicationController extends Controller
{
    public function list(Request $request)
    {
        return PublicationUseCases::list($request);
    }

    public function create(Request $request)
    {
        return PublicationUseCases::create($request);
    }

    public function update(int $id, Request $request)
    {
        return PublicationUseCases::update($id, $request);
    }

    public function delete(int $id)
    {
        return PublicationUseCases::delete($id);
    }
}
