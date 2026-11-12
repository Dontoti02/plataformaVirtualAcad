<?php

namespace Modules\Tenant\Modules\JobOpportunities\Company\Application\UseCases;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Modules\Shared\Utils\Response;
use Modules\Tenant\Modules\JobOpportunities\Company\Application\Actions\RegisterCompanyAction;
use Modules\Tenant\Modules\JobOpportunities\Company\Application\DTOs\RegisterCompanyDTO;

class RegisterCompanyUseCase
{
  public function __construct(
    protected RegisterCompanyAction $registerCompanyAction
  ) {}

  public function execute(RegisterCompanyDTO $dto): JsonResponse
  {
    try {
      $company = $this->registerCompanyAction->execute($dto);
      return Response::success($company, 'Compañía registrada correctamente.');
    } catch (Exception $e) {
      Log::error('Error al registrar empresa: ' . $e->getMessage());
      return Response::error('Error: ' . $e->getMessage());
    }
  }
}
