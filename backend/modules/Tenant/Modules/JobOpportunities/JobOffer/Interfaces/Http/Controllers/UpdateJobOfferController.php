<?php
namespace Modules\Tenant\Modules\JobOpportunities\JobOffer\Interfaces\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Tenant\Modules\JobOpportunities\JobOffer\Application\Requests\CreateJobOfferRequest;
use Modules\Tenant\Modules\JobOpportunities\JobOffer\Application\UseCases\UpdateobOfferUseCase;

class UpdateJobOfferController extends Controller
{
  public function __invoke(
    int $id,
    CreateJobOfferRequest $request,
    UpdateobOfferUseCase $useCase
  ): JsonResponse {
    $response = $useCase->execute($id,$request->validated());
    return $response;
  }
}
