<?php

namespace Modules\Tenant\Modules\JobOpportunities\JobOffer\Application\UseCases;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Shared\Utils\Response;
use Illuminate\Support\Facades\Log;
use Modules\Tenant\Modules\JobOpportunities\JobOffer\Application\Formatters\JobOfferPublicFormatter;
use Modules\Tenant\Modules\JobOpportunities\JobOffer\Infrastructure\Persistence\Eloquent\PublicJobOffersRepository;

class PublicJobOffersUseCase
{

  static function filters() {
    try {
      DB::beginTransaction();
      $filters = PublicJobOffersRepository::filters();
      DB::commit();
      return Response::success($filters, 'Filtros de ofertas de trabajo obtenidos correctamente.');
    } catch (Exception $e) {
      DB::rollBack();
      Log::error('Error al obtener los filtros: ' . $e->getMessage());
      return Response::error('Error al filtrar las ofertas de trabajo'. $e->getMessage());
    }
  }

  static function list($filters) {
    try {
      DB::beginTransaction();
      $jobOffers = PublicJobOffersRepository::list($filters);
      $formated = JobOfferPublicFormatter::formatPaginated($jobOffers);
      DB::commit();
      return Response::success($formated, 'Ofertas de trabajo obtenidas correctamente.');
    } catch (Exception $e) {
      DB::rollBack();
      Log::error('Error al obtener las ofertas: ' . $e->getMessage());
      return Response::error('Error al listar las ofertas de trabajo'. $e->getMessage());
    }
  }

  static function findSlug(Request $request,$slug) {
    try {
      DB::beginTransaction();
      $jobOffer = PublicJobOffersRepository::findSlug($request,$slug);
      if (!$jobOffer) {
        return Response::error('Oferta de trabajo no encontrada', 404);
      }
      DB::commit();
      return Response::success($jobOffer, 'Oferta de trabajo obtenida correctamente.');
    } catch (Exception $e) {
      DB::rollBack();
      Log::error('Error al obtener la oferta por slug: ' . $e->getMessage());
      return Response::error('Error al obtener la oferta de trabajo'. $e->getMessage());
    }
  }
}
