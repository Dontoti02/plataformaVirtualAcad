<?php
namespace Modules\Tenant\Modules\JobOpportunities\JobOffer\Interfaces\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Tenant\Modules\JobOpportunities\JobOffer\Application\Requests\CreateJobOfferRequest;
use Modules\Tenant\Modules\JobOpportunities\JobOffer\Application\UseCases\CreateJobOfferUseCase;

class CreateJobOfferController extends Controller
{
  public function __invoke(
    CreateJobOfferRequest $request,
    CreateJobOfferUseCase $useCase
  ): JsonResponse {
    $response = $useCase->execute($request->validated());
    return $response;
  }
}
