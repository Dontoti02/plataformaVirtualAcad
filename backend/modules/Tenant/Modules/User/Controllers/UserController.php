<?php

namespace Modules\Tenant\Modules\User\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Tenant\Modules\User\UseCases\UserUseCases;

class UserController extends Controller
{
    public function params()
    {
        return UserUseCases::params();
    }

    public function list(Request $request)
    {
        return UserUseCases::list($request);
    }

    public function create(Request $request)
    {
        return UserUseCases::create($request);
    }

    public function update(int $id, Request $request)
    {
        return UserUseCases::update($id, $request);
    }

    public function changePassword(int $id, Request $request)
    {
        return UserUseCases::changePassword($id, $request);
    }

    public function changePhoto(int $id, Request $request)
    {
        return UserUseCases::changePhoto($id, $request);
    }

    public function deletePhoto(int $id)
    {
        return UserUseCases::deletePhoto($id);
    }

    public function resetPassword(int $id)
    {
        return UserUseCases::resetPassword($id);
    }

    public function disable(int $id)
    {
        return UserUseCases::disable($id);
    }

    public function delete(int $id)
    {
        return UserUseCases::delete($id);
    }
}
