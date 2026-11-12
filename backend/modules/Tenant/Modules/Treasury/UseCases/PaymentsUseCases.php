<?php

namespace Modules\Tenant\Modules\Treasury\UseCases;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Shared\Utils\Response;
use Modules\Tenant\Modules\Treasury\Repositories\PaymentsRepository;

class PaymentsUseCases
{
  public static function get(int $person_id,int $is_paid)
  {
    try {
      $result = PaymentsRepository::get($person_id,$is_paid);
      return Response::success($result);
    } catch (Exception $e) {
      return Response::error($e->getMessage());
    }
  }

  public static function searchStudent(Request $request)
  {
    try {
      $result = PaymentsRepository::searchStudent($request);
      return Response::success($result);
    } catch (Exception $e) {
      return Response::error($e->getMessage());
    }
  }

  public static function create(Request $request)
  {
    try {
      DB::beginTransaction();
      $result = PaymentsRepository::create($request);
      DB::commit();
      return Response::success($result);
    } catch (Exception $e) {
      DB::rollBack();
      return Response::error($e->getMessage());
    }
  }

  public static function detail(int $id)
  {
    try {
      $result = PaymentsRepository::detail($id);
      return Response::success($result);
    } catch (Exception $e) {
      return Response::error($e->getMessage());
    }
  }
  public static function payNextDetail(int $id)
  {
    try {
      DB::beginTransaction();
      $result = PaymentsRepository::payNextDetail($id);
      DB::commit();
      return Response::success($result);
    } catch (Exception $e) {
      DB::rollBack();
      return Response::error($e->getMessage());
    }
  }

  public static function exportTicket($id)
  {
    try {
      DB::beginTransaction();
      $pdf = PaymentsRepository::exportTicket(
        $id
      );
      DB::commit();
      return $pdf->stream()
        ->header('Content-Type', 'application/pdf')
        ->header('Content-Disposition', 'inline; filename="boleta.pdf"')
        ->header('Content-Transfer-Encoding', 'binary');
    } catch (Exception $e) {
      return Response::error($e->getMessage());
    }
  }
  
  public static function exportMovementTicket($id)
  {
    try {
      DB::beginTransaction();
      $pdf = PaymentsRepository::exportMovementTicket(
        $id
      );
      DB::commit();
      return $pdf->stream()
        ->header('Content-Type', 'application/pdf')
        ->header('Content-Disposition', 'inline; filename="boleta.pdf"')
        ->header('Content-Transfer-Encoding', 'binary');
    } catch (Exception $e) {
      return Response::error($e->getMessage());
    }
  }
  public static function movementsByConcept(int $concept_id)
  {
    try {
      $result = PaymentsRepository::movementsByConcept($concept_id);
      return Response::success($result);
    } catch (Exception $e) {
      return Response::error($e->getMessage());
    }
  }

  public static function refund(Request $request)
  {
    try {
      DB::beginTransaction();
      $result = PaymentsRepository::refund($request);
      DB::commit();
      return Response::success($result);
    } catch (Exception $e) {
      DB::rollBack();
      return Response::error($e->getMessage());
    }
  }
  public static function payEnrollment(int $enrollId,Request $request){
    try {
      DB::beginTransaction();
      $result = PaymentsRepository::payEnrollment($enrollId,$request);
      DB::commit();
      return Response::success($result);
    } catch (Exception $e) {
      DB::rollBack();
      return Response::error($e->getMessage());
    }
  }
}
