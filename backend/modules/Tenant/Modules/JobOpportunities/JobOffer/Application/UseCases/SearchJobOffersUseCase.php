<?php

namespace Modules\Tenant\Modules\JobOpportunities\JobOffer\Application\UseCases;

use Illuminate\Http\JsonResponse;
use Modules\Shared\Helpers\SessionManager;
use Modules\Shared\Utils\Response;
use Modules\Tenant\Modules\JobOpportunities\JobOffer\Application\DTOs\ListJobOffersFilterDTO;
use Modules\Tenant\Modules\JobOpportunities\JobOffer\Application\Formatters\JobOfferFormatter;
use Modules\Tenant\Modules\JobOpportunities\JobOffer\Domain\Repositories\JobOfferRepositoryInterface;
use Modules\Tenant\Modules\User\Enums\RolTenant;
use Modules\Tenant\Modules\User\Models\User;

class SearchJobOffersUseCase
{
  public function __construct(
    protected JobOfferRepositoryInterface $repository
  ) {}

  public function execute(array $filters): JsonResponse
  {
    $session = SessionManager::get();
    $user = User::find($session->id);
    $isAdmin = $user->hasRole(RolTenant::ADMINISTRADOR);
    $isCompany = $user->hasRole(RolTenant::COMPANY);

    $this->validateFilters($filters);

    if (!$isAdmin && !$isCompany) {
      return Response::error('Rol no autorizado', 403);
    }

    $companyId = $filters['companyId'] ?? null;

    if ($isCompany) {
      $companyId = $user->company->id;
    }

    $dto = new ListJobOffersFilterDTO(
      companyId: $companyId,
      orderBy: $filters['orderBy'] ?? null,
      categoryId: $filters['categoryId'] ?? null,
      dateFilter: $filters['dateFilter'] ?? null,
      salary: $filters['salary'] ?? null,
      scheduleId: $filters['scheduleId'] ?? null,
      locationId: $filters['locationId'] ?? null,
      contractTypeId: $filters['contractTypeId'] ?? null,
      search: $filters['search'] ?? null,
      perPage: $filters['per_page'] ?? 10,
      page: $filters['page'] ?? 1,
    );
    $paginated = $this->repository->list($dto);
    $formatted = JobOfferFormatter::formatPaginated($paginated);

    return Response::success($formatted, 'Convocatorias filtradas');
  }

    public function validateFilters(array $filters): void
  {
    if (empty($filters)) {
      throw new \InvalidArgumentException('No se han enviado filtros');
    }
    if (isset($filters['per_page']) && !is_numeric($filters['per_page'])) {
      throw new \InvalidArgumentException('El valor de per_page debe ser un número');
    }
    if (isset($filters['page']) && !is_numeric($filters['page'])) {
      throw new \InvalidArgumentException('El valor de page debe ser un número');
    }
  }
}
