<?php

namespace Modules\Tenant\Modules\JobOpportunities\JobOffer\Application\UseCases;

use Illuminate\Http\JsonResponse;
use Modules\Shared\Utils\Response;
use Modules\Tenant\Modules\JobOpportunities\JobOffer\Application\Enums\JobOfferStateEnum;
use Modules\Tenant\Modules\JobOpportunities\JobOffer\Domain\Repositories\JobOfferRepositoryInterface;

class DeleteJobOfferUseCase
{
  public function __construct(
    protected JobOfferRepositoryInterface $repository
  ) {}

  public function execute($id): JsonResponse
  {
    $findJobOffer = $this->repository->findById($id);
    if (!$findJobOffer) {
      return Response::error('Convocatoria no encontrada', 404);
    }

    if ($findJobOffer->state_id === JobOfferStateEnum::DRAFT_ID || 
        $findJobOffer->state_id === JobOfferStateEnum::ACTIVE_ID) {
      return Response::error('No se puede eliminar una convocatoria en estado borrador o vigente.', 400);
    }

    $this->repository->delete($id);
    return Response::success([], 'Convocatoria eliminada ');
  }
}
