<?php

namespace Modules\Tenant\Modules\Training\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Tenant\Modules\Training\UseCases\TrainingUseCases;

class TrainingController extends Controller
{
    public function filters()
    {
        return TrainingUseCases::filters();
    }

    public function list(Request $request)
    {
        return TrainingUseCases::list($request);
    }

    public function get(int $id)
    {
        return TrainingUseCases::get($id);
    }

    public function set(Request $request)
    {
        return TrainingUseCases::set($request);
    }

    public function delete(int $id)
    {
        return TrainingUseCases::delete($id);
    }

    public function updateImage(Request $request)
    {
        return TrainingUseCases::updateImage($request);
    }

    public function deleteImage(int $id)
    {
        return TrainingUseCases::deleteImage($id);
    }

    public function updateFavorite(int $id)
    {
        return TrainingUseCases::updateFavorite($id);
    }

    public function findPerson(Request $request)
    {
        return TrainingUseCases::findPerson($request);
    }

    public function createPerson(Request $request)
    {
        return TrainingUseCases::createPerson($request);
    }

    public function assignTeacher(Request $request)
    {
        return TrainingUseCases::assignTeacher($request);
    }

    public function filtersStudents()
    {
        return TrainingUseCases::filtersStudents();
    }

    public function listStudents(Request $request)
    {
        return TrainingUseCases::listStudents($request);
    }

    public function listStudentsDownload(Request $request)
    {
        return TrainingUseCases::listStudentsDownload($request);
    }

    public function assignStudent(Request $request)
    {
        return TrainingUseCases::assignStudent($request);
    }

    public function unassignStudent(Request $request)
    {
        return TrainingUseCases::unassignStudent($request);
    }

    public function activateStudent(Request $request)
    {
        return TrainingUseCases::activateStudent($request);
    }

    public function certificateDownload(Request $request)
    {
        return TrainingUseCases::certificateDownload($request);
    }

    public function certificateDownloadZip(int $id)
    {
        return TrainingUseCases::certificateDownloadZip($id);
    }

    public function createCategory(Request $request)
    {
        return TrainingUseCases::createCategory($request);
    }
}
