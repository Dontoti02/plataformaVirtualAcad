<?php
namespace Modules\Tenant\Modules\JobOpportunities\JobOffer\Interfaces\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Tenant\Modules\JobOpportunities\JobOffer\Application\Requests\FindJobOfferUseRequest;
use Modules\Tenant\Modules\JobOpportunities\JobOffer\Application\UseCases\FindJobOfferUseCase;

class FindJobOfferController extends Controller
{
  public function __invoke(
    FindJobOfferUseRequest $request,
    FindJobOfferUseCase $useCase
  ): JsonResponse {
    $request->validated();
    $response = $useCase->execute($request->query('id'), $request->query('slug'));
    return $response;
  }
}
