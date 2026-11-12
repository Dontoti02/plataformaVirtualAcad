<?php

namespace Modules\Tenant\Modules\Training\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Tenant\Modules\Training\UseCases\TrainingEvaluationFormUseCases;

class TrainingEvaluationFormController extends Controller
{
    public function questionTypes()
    {
        return TrainingEvaluationFormUseCases::questionTypes();
    }

    public function get(string $uuid, int $person_id = 0)
    {
        return TrainingEvaluationFormUseCases::get($uuid, $person_id);
    }

    public function delivered(Request $request)
    {
        return TrainingEvaluationFormUseCases::delivered($request);
    }
}
