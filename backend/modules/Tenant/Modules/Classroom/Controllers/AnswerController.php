<?php

namespace Modules\Tenant\Modules\Classroom\Controllers;

use App\Http\Controllers\Controller;
use Modules\Tenant\Modules\Classroom\UseCases\AnswerUseCases;

class AnswerController extends Controller
{
    public function list(int $content_id)
    {
        return AnswerUseCases::list($content_id);
    }

    public function delivered(int $id)
    {
        return AnswerUseCases::delivered($id);
    }
}
