<?php

namespace Modules\Tenant\Modules\Treasury\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Tenant\Modules\Treasury\UseCases\ScalesUseCases;

class ScalesController extends Controller
{
    public function create(Request $request)
    {
      return ScalesUseCases::create($request);
    }
    public function all(Request $request)
    {
      return ScalesUseCases::all($request);
    }
    public function update(Request $request,int $id)
    {
      return ScalesUseCases::update($request,$id);
    }
    public function delete(int $id)
    {
      return ScalesUseCases::delete($id);
    }
    public function enrollments(int $id)
    {
      return ScalesUseCases::enrollments($id);
    }
}