<?php

namespace Modules\Tenant\Modules\Classroom\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Tenant\Modules\Classroom\UseCases\ContentUseCases;

class ContentController extends Controller
{
    public function list(int $classroom_id)
    {
        return ContentUseCases::list($classroom_id);
    }

    public function detail(int $id)
    {
        return ContentUseCases::detail($id);
    }

    public function create(Request $request)
    {
        return ContentUseCases::create($request);
    }

    public function update(int $id, Request $request)
    {
        return ContentUseCases::update($id, $request);
    }

    public function updateVisibility(int $id)
    {
        return ContentUseCases::updateVisibility($id);
    }

    public function updateStatus(int $id)
    {
        return ContentUseCases::updateStatus($id);
    }

    public function delete(int $id)
    {
        return ContentUseCases::delete($id);
    }
}
