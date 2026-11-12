<?php

namespace Modules\Tenant\Modules\Schedule\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Tenant\Modules\Schedule\UseCases\ScheduleUseCases;

class ScheduleController extends Controller
{
    public function filters()
    {
        return ScheduleUseCases::filters();
    }

    public function filtersByExport(Request $request)
    {
        return ScheduleUseCases::filtersByExport($request);
    }

    public function list(Request $request)
    {
        return ScheduleUseCases::list($request);
    }

    public function listClassrooms(Request $request)
    {
        return ScheduleUseCases::listClassrooms($request);
    }

    public function listClassroomsExisting(Request $request)
    {
        return ScheduleUseCases::listClassroomsExisting($request);
    }

    public function listTeachers(Request $request)
    {
        return ScheduleUseCases::listTeachers($request);
    }

    public function create(Request $request)
    {
        return ScheduleUseCases::create($request);
    }

    public function update(int $id, Request $request)
    {
        return ScheduleUseCases::update($id, $request);
    }

    public function delete(int $id)
    {
        return ScheduleUseCases::delete($id);
    }

    public function assignTeacher(Request $request)
    {
        return ScheduleUseCases::assignTeacher($request);
    }
}
