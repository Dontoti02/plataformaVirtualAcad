<?php

namespace Modules\Tenant\Modules\File\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Tenant\Modules\File\UseCases\LinkUseCases;

class LinkController extends Controller
{
    public function create(Request $request)
    {
        return LinkUseCases::create($request);
    }

    public function delete(int $id, string $model)
    {
        return LinkUseCases::delete($id, $model);
    }
}
