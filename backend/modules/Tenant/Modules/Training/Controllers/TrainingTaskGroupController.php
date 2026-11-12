<?php

namespace Modules\Tenant\Modules\Training\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Tenant\Modules\Training\UseCases\TrainingTaskGroupUseCases;

class TrainingTaskGroupController extends Controller
{
    public function list(int $training_content_id)
    {
        return TrainingTaskGroupUseCases::list($training_content_id);
    }

    public function set(Request $request)
    {
        return TrainingTaskGroupUseCases::set($request);
    }

    public function delete(int $id)
    {
        return TrainingTaskGroupUseCases::delete($id);
    }

    public function listParticipants(int $training_id)
    {
        return TrainingTaskGroupUseCases::listParticipants($training_id);
    }

    public function setParticipant(Request $request)
    {
        return TrainingTaskGroupUseCases::setParticipant($request);
    }

    public function deleteParticipant(int $id)
    {
        return TrainingTaskGroupUseCases::deleteParticipant($id);
    }
}
