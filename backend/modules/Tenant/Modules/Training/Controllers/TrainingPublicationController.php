<?php

namespace Modules\Tenant\Modules\Training\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Tenant\Modules\Training\UseCases\TrainingPublicationUseCases;

class TrainingPublicationController extends Controller
{
    public function list(Request $request)
    {
        return TrainingPublicationUseCases::list($request);
    }

    public function create(Request $request)
    {
        return TrainingPublicationUseCases::create($request);
    }

    public function update(int $id, Request $request)
    {
        return TrainingPublicationUseCases::update($id, $request);
    }

    public function delete(int $id)
    {
        return TrainingPublicationUseCases::delete($id);
    }
}
