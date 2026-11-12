<?php

namespace Modules\Tenant\Modules\Training\Controllers;

use App\Http\Controllers\Controller;
use Modules\Tenant\Modules\Training\UseCases\TrainingAnswerUseCases;

class TrainingAnswerController extends Controller
{
    public function list(int $training_content_id)
    {
        return TrainingAnswerUseCases::list($training_content_id);
    }

    public function delivered(int $id)
    {
        return TrainingAnswerUseCases::delivered($id);
    }
}
