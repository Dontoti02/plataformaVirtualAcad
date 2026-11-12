<?php

namespace Modules\Tenant\Modules\Classroom\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Tenant\Modules\Classroom\UseCases\CapacityUseCases;

class CapacityController extends Controller
{
    public function list(int $classroomId)
    {
        return CapacityUseCases::list($classroomId);
    }

    public function create(Request $request)
    {
        return CapacityUseCases::create($request);
    }

    public function createWithIndicators(Request $request)
    {
        return CapacityUseCases::createWithIndicators($request);
    }

    public function update(int $id, Request $request)
    {
        return CapacityUseCases::update($id, $request);
    }

    public function updateWithIndicators(int $id, Request $request)
    {
        return CapacityUseCases::updateWithIndicators($id, $request);
    }

    public function sort(int $id, int $position)
    {
        return CapacityUseCases::sort($id, $position);
    }

    public function delete(int $id)
    {
        return CapacityUseCases::delete($id);
    }

    public function createIndicator(Request $request)
    {
        return CapacityUseCases::createIndicator($request);
    }

    public function updateIndicator(int $id, Request $request)
    {
        return CapacityUseCases::updateIndicator($id, $request);
    }

    public function sortIndicator(int $id, int $position)
    {
        return CapacityUseCases::sortIndicator($id, $position);
    }

    public function deleteIndicator(int $id)
    {
        return CapacityUseCases::deleteIndicator($id);
    }
}
