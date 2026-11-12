<?php
namespace Modules\Tenant\Modules\JobOpportunities\JobOffer\Interfaces\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Tenant\Modules\JobOpportunities\JobOffer\Application\Requests\ChangeOfferStatusRequest;
use Modules\Tenant\Modules\JobOpportunities\JobOffer\Application\UseCases\ChangeOfferStatusUseCase;
class ChangeOfferStatusController extends Controller
{
  public function __invoke(
    ChangeOfferStatusRequest $request,
    ChangeOfferStatusUseCase $useCase
  ): JsonResponse {
    $request->validated();
    $response = $useCase->execute($request->validated());
    return $response;
  }
}
