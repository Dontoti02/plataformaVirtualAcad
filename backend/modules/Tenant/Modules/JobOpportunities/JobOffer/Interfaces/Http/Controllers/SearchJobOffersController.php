<?php
namespace Modules\Tenant\Modules\JobOpportunities\JobOffer\Interfaces\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Tenant\Modules\JobOpportunities\JobOffer\Application\Requests\SearchJobOffersRequest;
use Modules\Tenant\Modules\JobOpportunities\JobOffer\Application\UseCases\SearchJobOffersUseCase;

class SearchJobOffersController extends Controller
{
  public function __invoke(
    SearchJobOffersRequest $request,
    SearchJobOffersUseCase $useCase
  ): JsonResponse {
    $response = $useCase->execute($request->validated());
    return $response;
  }
}
