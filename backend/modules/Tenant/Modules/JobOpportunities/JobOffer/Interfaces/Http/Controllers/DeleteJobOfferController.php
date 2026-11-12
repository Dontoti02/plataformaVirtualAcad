<?php
namespace Modules\Tenant\Modules\JobOpportunities\JobOffer\Interfaces\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Tenant\Modules\JobOpportunities\JobOffer\Application\Requests\DeleteJobOfferUseRequest;
use Modules\Tenant\Modules\JobOpportunities\JobOffer\Application\UseCases\DeleteJobOfferUseCase;

class DeleteJobOfferController extends Controller
{
  public function __invoke(
    $id,
    DeleteJobOfferUseRequest $request,
    DeleteJobOfferUseCase $useCase
  ): JsonResponse {
    $request->validated();
    $response = $useCase->execute($id);
    return $response;
  }
}
