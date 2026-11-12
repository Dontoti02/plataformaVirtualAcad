<?php

namespace Modules\Tenant\Modules\JobOpportunities\JobOffer\Application\UseCases;

use Illuminate\Http\JsonResponse;
use Modules\Shared\Helpers\SessionManager;
use Modules\Shared\Utils\Response;
use Modules\Tenant\Modules\JobOpportunities\JobOffer\Application\DTOs\FiltersJobOfferDTO;
use Modules\Tenant\Modules\JobOpportunities\JobOffer\Domain\Repositories\JobOfferRepositoryInterface;
use Modules\Tenant\Modules\User\Enums\RolTenant;
use Modules\Tenant\Modules\User\Models\User;

class FiltersJobOffersUseCase
{
  public function __construct(
    protected JobOfferRepositoryInterface $repository
  ) {}

  public function execute(): JsonResponse
  {
    $session = SessionManager::get();
    $user = User::find($session->id);
    $isAdmin = $user->hasRole(RolTenant::ADMINISTRADOR);
    $isCompany = $user->hasRole(RolTenant::COMPANY);
    $dto = new FiltersJobOfferDTO(
      isAdmin: $isAdmin,
      isCompany: $isCompany,
      companyId: $isAdmin ? null : ($isCompany ? $user->company->id : null),
    );
    $filters = $this->repository->filters($dto);
    return Response::success($filters, 'Filtros de convocatorias');

  }
}
