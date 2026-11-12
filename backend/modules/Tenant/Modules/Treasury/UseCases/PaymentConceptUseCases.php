<?php

namespace Modules\Tenant\Modules\Treasury\UseCases;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Shared\Utils\Response;
use Modules\Tenant\Modules\Treasury\Repositories\PaymentConceptRepository;

class PaymentConceptUseCases
{
  public static function all(Request $request)
  {
    try {
      $result = PaymentConceptRepository::all($request);
      return Response::success($result);
    } catch (Exception $e) {
      return Response::error($e->getMessage());
    }
  }

  public static function create(Request $request)
  {
    try {
      DB::beginTransaction();
      $result = PaymentConceptRepository::create($request);
      DB::commit();
      return Response::success($result,'Payment concept created successfully');
    } catch (Exception $e) {
      DB::rollBack();
      return Response::error($e->getMessage());
    }
  }

  public static function update(int $id, Request $request)
  {
    try {
      DB::beginTransaction();
      $result = PaymentConceptRepository::update($id, $request);
      DB::commit();
      return Response::success($result,'Payment concept updated successfully');
    } catch (Exception $e) {
      DB::rollBack();
      return Response::error($e->getMessage());
    }
  }


  public static function active(int $id)
  {
    try {
      DB::beginTransaction();
      $result = PaymentConceptRepository::active($id);
      DB::commit();
      return Response::success($result,'Concepto de pago activado correctamente');
    } catch (Exception $e) {
      DB::rollBack();
      return Response::error($e->getMessage());
    }
  }

  public static function inactive(int $id)
  {
    try {
      DB::beginTransaction();
      $result = PaymentConceptRepository::inactive($id);
      DB::commit();
      return Response::success($result,'Concepto de pago desactivado correctamente');
    } catch (Exception $e) {
      DB::rollBack();
      return Response::error($e->getMessage());
    }
  }

  public static function delete(int $id)
  {
    try {
      DB::beginTransaction();
      $result = PaymentConceptRepository::delete($id);
      DB::commit();
      return Response::success($result,'Concepto de pago eliminado correctamente');
    } catch (Exception $e) {
      DB::rollBack();
      return Response::error($e->getMessage());
    }
  }
  public static function search(Request $request)
  {
    try {
      DB::beginTransaction();
      $result = PaymentConceptRepository::search($request);
      DB::commit();
      return Response::success($result);
    } catch (Exception $e) {
      DB::rollBack();
      return Response::error($e->getMessage());
    }
  }
  public static function enrollmentConceptsData()
  {
    try {
      DB::beginTransaction();
      $result = PaymentConceptRepository::enrollmentConceptsData();
      DB::commit();
      return Response::success($result);
    } catch (Exception $e) {
      DB::rollBack();
      return Response::error($e->getMessage());
    }
  }
  public static function history(int $id)
  {
    try {
      DB::beginTransaction();
      $result = PaymentConceptRepository::history($id);
      DB::commit();
      return Response::success($result);
    } catch (Exception $e) {
      DB::rollBack();
      return Response::error($e->getMessage());
    }
  }
  public static function movements(int $id)
  {
    try {
      DB::beginTransaction();
      $result = PaymentConceptRepository::movements($id);
      DB::commit();
      return Response::success($result);
    } catch (Exception $e) {
      DB::rollBack();
      return Response::error($e->getMessage());
    }
  }
}
