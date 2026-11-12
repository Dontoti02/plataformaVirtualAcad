<?php

namespace Modules\Tenant\Modules\Classroom\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Tenant\Modules\Classroom\UseCases\ContentGroupUseCases;

class ContentGroupController extends Controller
{
    public function list(int $classroom_id)
    {
        return ContentGroupUseCases::list($classroom_id);
    }

    public function create(Request $request)
    {
        return ContentGroupUseCases::create($request);
    }

    public function update(int $id, Request $request)
    {
        return ContentGroupUseCases::update($id, $request);
    }

    public function delete(int $id)
    {
        return ContentGroupUseCases::delete($id);
    }
}
