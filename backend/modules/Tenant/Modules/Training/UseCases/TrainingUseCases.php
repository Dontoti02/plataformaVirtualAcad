<?php

namespace Modules\Tenant\Modules\Training\UseCases;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Shared\Utils\Response;
use Modules\Tenant\Modules\Training\Repositories\TrainingRepository;

class TrainingUseCases
{
    public static function filters()
    {
        try {
            $result = TrainingRepository::filters();
            return Response::success($result);
        } catch (Exception $e) {
            return Response::error($e->getMessage());
        }
    }

    public static function list(Request $request)
    {
        try {
            $result = TrainingRepository::list($request);
            return Response::success($result);
        } catch (Exception $e) {
            return Response::error($e->getMessage());
        }
    }

    public static function get(int $id)
    {
        try {
            $result = TrainingRepository::get($id);
            return Response::success($result);
        } catch (Exception $e) {
            return Response::error($e->getMessage());
        }
    }

    public static function set(Request $request)
    {
        DB::beginTransaction();
        try {
            $result = TrainingRepository::set($request);
            DB::commit();
            return Response::success($result);
        } catch (Exception $e) {
            DB::rollBack();
            return Response::error($e->getMessage());
        }
    }

    public static function delete(int $id)
    {
        DB::beginTransaction();
        try {
            $result = TrainingRepository::delete($id);
            DB::commit();
            return Response::success($result);
        } catch (Exception $e) {
            DB::rollBack();
            return Response::error($e->getMessage());
        }
    }

    public static function updateImage(Request $request)
    {
        try {
            $result = TrainingRepository::updateImage($request);
            return Response::success($result);
        } catch (Exception $e) {
            return Response::error($e->getMessage());
        }
    }

    public static function deleteImage(int $id)
    {
        try {
            $result = TrainingRepository::deleteImage($id);
            return Response::success($result);
        } catch (Exception $e) {
            return Response::error($e->getMessage());
        }
    }

    public static function updateFavorite(int $id)
    {
        DB::beginTransaction();
        try {
            $result = TrainingRepository::updateFavorite($id);
            DB::commit();
            return Response::success($result);
        } catch (Exception $e) {
            DB::rollBack();
            return Response::error($e->getMessage());
        }
    }

    public static function findPerson(Request $request)
    {
        DB::beginTransaction();
        try {
            $result = TrainingRepository::findPerson($request);
            DB::commit();
            return Response::success($result);
        } catch (Exception $e) {
            DB::rollBack();
            return Response::error($e->getMessage());
        }
    }

    public static function createPerson(Request $request)
    {
        DB::beginTransaction();
        try {
            $result = TrainingRepository::createPerson($request);
            DB::commit();
            return Response::success($result);
        } catch (Exception $e) {
            DB::rollBack();
            return Response::error($e->getMessage());
        }
    }

    public static function assignTeacher(Request $request)
    {
        DB::beginTransaction();
        try {
            $result = TrainingRepository::assignTeacher($request);
            DB::commit();
            return Response::success($result);
        } catch (Exception $e) {
            DB::rollBack();
            return Response::error($e->getMessage());
        }
    }

    public static function filtersStudents()
    {
        try {
            $result = TrainingRepository::filtersStudents();
            return Response::success($result);
        } catch (Exception $e) {
            return Response::error($e->getMessage());
        }
    }

    public static function listStudents(Request $request)
    {
        try {
            $result = TrainingRepository::listStudents($request);
            return Response::success($result);
        } catch (Exception $e) {
            return Response::error($e->getMessage());
        }
    }

    public static function listStudentsDownload(Request $request)
    {
        try {
            [$binary, $type, $filename] = TrainingRepository::listStudentsDownload($request);
            return Response::file($binary, $type, $filename);
        } catch (Exception $e) {
            return Response::error($e->getMessage());
        }
    }

    public static function assignStudent(Request $request)
    {
        DB::beginTransaction();
        try {
            $result = TrainingRepository::assignStudent($request);
            DB::commit();
            return Response::success($result);
        } catch (Exception $e) {
            DB::rollBack();
            return Response::error($e->getMessage());
        }
    }

    public static function unassignStudent(Request $request)
    {
        DB::beginTransaction();
        try {
            $result = TrainingRepository::unassignStudent($request);
            DB::commit();
            return Response::success($result);
        } catch (Exception $e) {
            DB::rollBack();
            return Response::error($e->getMessage());
        }
    }

    public static function activateStudent(Request $request)
    {
        DB::beginTransaction();
        try {
            $result = TrainingRepository::activateStudent($request);
            DB::commit();
            return Response::success($result);
        } catch (Exception $e) {
            DB::rollBack();
            return Response::error($e->getMessage());
        }
    }

    public static function certificateDownload(Request $request)
    {
        try {
            [$binary, $type, $filename] = TrainingRepository::certificateDownload($request);
            return Response::file($binary, $type, $filename);
        } catch (Exception $e) {
            return Response::error($e->getMessage());
        }
    }

    public static function certificateDownloadZip(int $id)
    {
        try {
            [$binary, $type, $filename] = TrainingRepository::certificateDownloadZip($id);
            return Response::file($binary, $type, $filename);
            // return  TrainingRepository::certificateDownloadZip($id);
        } catch (Exception $e) {
            return Response::error($e->getMessage());
        }
    }

    public static function createCategory(Request $request)
    {
        DB::beginTransaction();
        try {
            $result = TrainingRepository::createCategory($request);
            DB::commit();
            return Response::success($result);
        } catch (Exception $e) {
            DB::rollBack();
            return Response::error($e->getMessage());
        }
    }
}
