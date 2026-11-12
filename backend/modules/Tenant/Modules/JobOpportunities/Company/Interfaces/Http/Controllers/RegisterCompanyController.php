<?php

namespace Modules\Tenant\Modules\JobOpportunities\Company\Interfaces\Http\Controllers;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Tenant\Modules\JobOpportunities\Company\Application\DTOs\RegisterCompanyDTO;
use Modules\Tenant\Modules\JobOpportunities\Company\Application\Requests\RegisterCompanyRequest;
use Modules\Tenant\Modules\JobOpportunities\Company\Application\UseCases\RegisterCompanyUseCase;

class RegisterCompanyController extends Controller
{
  public function __invoke(
    RegisterCompanyRequest $request,
    RegisterCompanyUseCase $useCase
  ): JsonResponse {
    $dto = new RegisterCompanyDTO(...$request->validated());
    $response = $useCase->execute($dto);
    return $response;
  }
}
