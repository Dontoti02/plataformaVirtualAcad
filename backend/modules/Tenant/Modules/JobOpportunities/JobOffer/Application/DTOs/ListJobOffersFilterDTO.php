<?php

namespace Modules\Tenant\Modules\JobOpportunities\JobOffer\Application\DTOs;

class ListJobOffersFilterDTO
{
  public function __construct(
    public readonly ?int $companyId = null,
    public readonly ?string $orderBy = null,
    public readonly ?int $categoryId = null,
    public readonly ?string $dateFilter = null,
    public readonly ?string $salary = null,
    public readonly ?int $scheduleId = null,
    public readonly ?int $locationId = null,
    public readonly ?int $contractTypeId = null,
    public readonly ?string $search = null,
    public readonly int $perPage = 10,
    public readonly int $page = 1,
  ) {}
}
