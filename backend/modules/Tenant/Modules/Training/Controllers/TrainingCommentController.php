<?php

namespace Modules\Tenant\Modules\Training\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Tenant\Modules\Training\UseCases\TrainingCommentUseCases;

class TrainingCommentController extends Controller
{
    public function create(Request $request)
    {
        return TrainingCommentUseCases::create($request);
    }

    public function update(int $id, Request $request)
    {
        return TrainingCommentUseCases::update($id, $request);
    }

    public function delete(int $id)
    {
        return TrainingCommentUseCases::delete($id);
    }
}
