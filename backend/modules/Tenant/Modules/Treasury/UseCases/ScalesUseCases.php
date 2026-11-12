<?php

namespace Modules\Tenant\Modules\Treasury\UseCases;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Shared\Utils\Response;
use Modules\Tenant\Modules\Treasury\Repositories\ScalesRepository;

class ScalesUseCases
{

  public static function create(Request $request)
  {
    try {
      DB::beginTransaction();
      $result = ScalesRepository::create($request);
      DB::commit();
      return Response::success($result,'Payment concept created successfully');
    } catch (Exception $e) {
      DB::rollBack();
      return Response::error($e->getMessage());
    }
  }
  public static function all (Request $request)
  {
    try {
      $result = ScalesRepository::all($request);
      return Response::success($result);
    } catch (Exception $e) {
      return Response::error($e->getMessage());
    }
  }

  public static function update(Request $request,int $id)
  {
    try {
      DB::beginTransaction();
      $result = ScalesRepository::update($request,$id);
      DB::commit();
      return Response::success($result,'Payment concept updated successfully');
    } catch (Exception $e) {
      DB::rollBack();
      return Response::error($e->getMessage());
    }
  }

  public static function delete(int $id)
  {
    try {
      DB::beginTransaction();
      $result = ScalesRepository::delete($id);
      DB::commit();
      return Response::success($result,'Payment concept deleted successfully');
    } catch (Exception $e) {
      DB::rollBack();
      return Response::error($e->getMessage());
    }
  }
  public static function enrollments(int $id)
  {
    try {
      DB::beginTransaction();
      $result = ScalesRepository::enrollments($id);
      DB::commit();
      return Response::success($result,'Payment concept deleted successfully');
    } catch (Exception $e) {
      DB::rollBack();
      return Response::error($e->getMessage());
    }
  }

}
