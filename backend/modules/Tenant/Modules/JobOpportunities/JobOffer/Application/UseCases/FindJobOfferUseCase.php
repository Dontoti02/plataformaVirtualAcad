<?php

namespace Modules\Tenant\Modules\JobOpportunities\JobOffer\Application\UseCases;

use Illuminate\Http\JsonResponse;
use Modules\Shared\Utils\Response;
use Modules\Tenant\Modules\JobOpportunities\JobOffer\Application\Formatters\JobOfferFindFormatter;
use Modules\Tenant\Modules\JobOpportunities\JobOffer\Domain\Repositories\JobOfferRepositoryInterface;

class FindJobOfferUseCase
{
  public function __construct(
    protected JobOfferRepositoryInterface $repository
  ) {}

  public function execute($queryId, $querySlug): JsonResponse
  {
    if ($queryId) {
      $result = $this->repository->findById($queryId);
    }

    if ($querySlug) {
      $result = $this->repository->findBySlug($querySlug);
    }

    $formatted = JobOfferFindFormatter::format($result);

    return Response::success($formatted, 'Convocatoria encontrada'. ($queryId ? ' por ID' : ' por Slug'));
  }
}
