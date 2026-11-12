<?php

namespace Modules\Tenant\Modules\Enroll\UseCases;

use Exception;
use Illuminate\Http\Request;
use Modules\Shared\Utils\Response;
use Modules\Tenant\Modules\Enroll\Repositories\EnrollRepository;

class EnrollUseCases
{
  public static function list(Request $request)
  {
    try {
      $result = EnrollRepository::list($request);
      return Response::success($result);
    } catch (Exception $e) {
      return Response::error($e->getMessage());
    }
  }
  public static function get(int $id)
  {
    try {
      $result = EnrollRepository::get($id);
      return Response::success($result);
    } catch (Exception $e) {
      return Response::error($e->getMessage());
    }
  }
  public static function getFormsData()
  {
    try {
      $result = EnrollRepository::getFormsData();
      return Response::success($result);
    } catch (Exception $e) {
      return Response::error($e->getMessage());
    }
  }
  public static function SearchStudent(Request $request)
  {
    try {
      $result = EnrollRepository::SearchStudent($request);
      return Response::success($result);
    } catch (Exception $e) {
      return Response::error($e->getMessage());
    }
  }
  public static function validateDNI(Request $request)
  {
    try {
      $result = EnrollRepository::validateDNI($request);
      return Response::success($result);
    } catch (Exception $e) {
      return Response::error($e->getMessage());
    }
  }
  public static function enrollRegularStudent(Request $request)
  {
    try {
      $result = EnrollRepository::enrollRegularStudent($request);
      return Response::success($result);
    } catch (Exception $e) {
      return Response::error($e->getMessage());
    }
  }
  public static function enrollNewStudent(Request $request)
  {
    try {
      $result = EnrollRepository::enrollNewStudent($request);
      return Response::success($result);
    } catch (Exception $e) {
      return Response::error($e->getMessage());
    }
  }
  public static function validateFamilyDNI(Request $request)
  {
    try {
      $result = EnrollRepository::validateFamilyDNI($request);
      return Response::success($result);
    } catch (Exception $e) {
      return Response::error($e->getMessage());
    }
  }
  public static function validateEnrollment(Request $request)
  {
    try {
      $result = EnrollRepository::validateEnrollment($request);
      return Response::success($result);
    } catch (Exception $e) {
      return Response::error($e->getMessage());
    }
  }
  public static function delete(int $id)
  {
    try {
      $result = EnrollRepository::delete($id);
      return Response::success($result);
    } catch (Exception $e) {
      return Response::error($e->getMessage());
    }
  }
  public static function updateEnroll(int $id, Request $request)
  {
    try {
      $result = EnrollRepository::updateEnroll($id, $request);
      return Response::success($result);
    } catch (Exception $e) {
      return Response::error($e->getMessage());
    }
  }

  public static function getValidationsForEnrollment() {
    try {
      $result = EnrollRepository::getValidationsForEnrollment();
      return Response::success($result);
    } catch (Exception $e) {
      return Response::error($e->getMessage());
    }
  }
}
