<?php

namespace Modules\Tenant\Modules\JobOpportunities\Applicants\UseCases;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Shared\Utils\Response;
use Illuminate\Support\Facades\Log;
use Modules\Tenant\Modules\JobOpportunities\Applicants\Repository\ApplicantRepository;

class ApplicantUseCase
{
  public static function checkOffer($slug)
  {
    try {
      $offer = ApplicantRepository::checkOffer($slug);
      if (!$offer) {
        return Response::error('Offer not found');
      }
      return Response::success($offer, 'Offer found successfully');
    } catch (\Exception $e) {
      return Response::error('An error occurred while checking the offer' . $e->getMessage());
    }
  }

  public static function uploadCV($request)
  {
    try {
      DB::beginTransaction();
      $data = ApplicantRepository::uploadCV($request);
      DB::commit();
      return Response::success($data, 'CV subido exitosamente');
    } catch (\Exception $e) {
      DB::rollBack();
      return Response::error('An error occurred while uploading the CV');
    }
  }

  public static function applyOffer($request)
  {
    try {
      DB::beginTransaction();
      $data = ApplicantRepository::applyOffer($request);
      DB::commit();
      return Response::success($data, 'Aplicaste a la oferta exitosamente');
    } catch (\Exception $e) {
      DB::rollBack();
      return Response::error('Ocurrió un error al aplicar a la oferta: ' . $e->getMessage());
    }
  }

  public static function myApplications(Request $request)
  {
    try {
      $applications = ApplicantRepository::myApplications($request);
      return Response::success($applications, 'Mis postulaciones obtenidas correctamente');
    } catch (\Exception $e) {
      return Response::error('Ocurrió un error al obtener mis postulaciones: ' . $e->getMessage());
    }
  }

  public static function cancelApplication($id)
  {
    try {
      DB::beginTransaction();
      $data = ApplicantRepository::cancelApplication($id);
      DB::commit();
      return Response::success($data, 'Postulación anulada exitosamente.');
    } catch (\Exception $e) {
      DB::rollBack();
      return Response::error('Ocurrió un error al cancelar la postulación: ' . $e->getMessage());
    }
  }

  public static function myCvs()
  {
    try {
      $cvs = ApplicantRepository::myCvs();
      return Response::success($cvs, 'Mis CVs obtenidos correctamente');
    } catch (\Exception $e) {
      return Response::error('Ocurrió un error al obtener mis CVs: ' . $e->getMessage());
    }
  }

  public static function deleteMyCv($id)
  {
    try {
      DB::beginTransaction();
      $data = ApplicantRepository::deleteMyCv($id);
      DB::commit();
      return Response::success($data, 'CV eliminado exitosamente');
    } catch (\Exception $e) {
      DB::rollBack();
      return Response::error('Ocurrió un error al eliminar el CV: ' . $e->getMessage());
    }
  }

  public static function applicationsByOfferFilters($offerId,$companyId)
  {
    try {
      $applications = ApplicantRepository::applicationsByOfferFilters($offerId, $companyId);
      return Response::success($applications);
    } catch (\Exception $e) {
      return Response::error('Ocurrió un error al obtener las filtros para postulaciones: ' . $e->getMessage());
    }
  }

  public static function applicationsByOffer($offerId, $companyId = null)
  {
    try {
      $applications = ApplicantRepository::applicationsByOffer($offerId, $companyId);
      return Response::success($applications, 'Postulaciones obtenidas correctamente');
    } catch (\Exception $e) {
      return Response::error('Ocurrió un error al obtener las postulaciones: ' . $e->getMessage());
    }
  }

  public static function setState($id, $request)
  {
    try {
      DB::beginTransaction();
      $data = ApplicantRepository::setState($id, $request);
      DB::commit();
      return Response::success($data, 'Estado actualizado exitosamente');
    } catch (\Exception $e) {
      DB::rollBack();
      Log::error('Error : ' . $e->getMessage());
      return Response::error('Ocurrió un error al actualizar el estado: ' . $e->getMessage());
    }
  }
}
