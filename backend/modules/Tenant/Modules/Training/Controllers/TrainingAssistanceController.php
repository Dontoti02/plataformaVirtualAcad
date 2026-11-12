<?php

namespace Modules\Tenant\Modules\Training\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Tenant\Modules\Training\UseCases\TrainingAssistanceUseCases;

class TrainingAssistanceController extends Controller
{
    public function dates(int $training_id)
    {
        return TrainingAssistanceUseCases::dates($training_id);
    }

    public function list(Request $request)
    {
        return TrainingAssistanceUseCases::list($request);
    }

    public function create(Request $request)
    {
        return TrainingAssistanceUseCases::create($request);
    }

    public function mark(int $id, Request $request)
    {
        return TrainingAssistanceUseCases::mark($id, $request);
    }
    public function deleteMultiple(Request $request)
    {
        return TrainingAssistanceUseCases::deleteMultiple($request);
    }
}
