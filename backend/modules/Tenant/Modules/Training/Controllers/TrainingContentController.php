<?php

namespace Modules\Tenant\Modules\Training\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Tenant\Modules\Training\UseCases\TrainingContentUseCases;

class TrainingContentController extends Controller
{
    public function list(int $training_id)
    {
        return TrainingContentUseCases::list($training_id);
    }

    public function detail(int $id)
    {
        return TrainingContentUseCases::detail($id);
    }

    public function create(Request $request)
    {
        return TrainingContentUseCases::create($request);
    }

    public function update(int $id, Request $request)
    {
        return TrainingContentUseCases::update($id, $request);
    }

    public function updateVisibility(int $id)
    {
        return TrainingContentUseCases::updateVisibility($id);
    }

    public function updateStatus(int $id)
    {
        return TrainingContentUseCases::updateStatus($id);
    }

    public function delete(int $id)
    {
        return TrainingContentUseCases::delete($id);
    }
}
