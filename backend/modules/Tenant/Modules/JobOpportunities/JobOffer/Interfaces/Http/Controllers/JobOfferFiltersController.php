<?php
namespace Modules\Tenant\Modules\JobOpportunities\JobOffer\Interfaces\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Tenant\Modules\JobOpportunities\JobOffer\Application\UseCases\FiltersJobOffersUseCase;

class JobOfferFiltersController extends Controller
{
  public function __invoke(
    FiltersJobOffersUseCase $useCase
  ): JsonResponse
  {
    $response = $useCase->execute();
    return $response;
  }
}
