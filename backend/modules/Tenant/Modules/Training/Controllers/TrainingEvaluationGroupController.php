<?php

namespace Modules\Tenant\Modules\Training\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Tenant\Modules\Training\UseCases\TrainingEvaluationGroupUseCases;

class TrainingEvaluationGroupController extends Controller
{
    public function list(int $training_id)
    {
        return TrainingEvaluationGroupUseCases::list($training_id);
    }

    public function set(Request $request)
    {
        return TrainingEvaluationGroupUseCases::set($request);
    }
}
