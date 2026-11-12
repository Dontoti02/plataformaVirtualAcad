<?php

namespace Modules\Tenant\Modules\Classroom\Controllers;

use App\Http\Controllers\Controller;
use Modules\Tenant\Modules\Classroom\UseCases\ParticipantUseCases;

class ParticipantController extends Controller
{

    public function list(int $classroom_id)
    {
        return ParticipantUseCases::list($classroom_id);
    }
}
