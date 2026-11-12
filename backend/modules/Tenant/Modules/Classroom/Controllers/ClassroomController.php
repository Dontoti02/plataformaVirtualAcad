<?php

namespace Modules\Tenant\Modules\Classroom\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Tenant\Modules\Classroom\UseCases\ClassroomUseCases;

class ClassroomController extends Controller
{
    public function list(Request $request)
    {
        return ClassroomUseCases::list($request);
    }

    public function options(Request $request)
    {
        return ClassroomUseCases::options($request);
    }

    public function get(int $id)
    {
        return ClassroomUseCases::get($id);
    }

    public function updateImage(Request $request)
    {
        return ClassroomUseCases::updateImage($request);
    }

    public function updateFavorite(int $id)
    {
        return ClassroomUseCases::updateFavorite($id);
    }
}
