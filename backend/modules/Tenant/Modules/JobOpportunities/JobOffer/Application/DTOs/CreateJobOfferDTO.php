<?php

namespace Modules\Tenant\Modules\JobOpportunities\JobOffer\Application\DTOs;

class CreateJobOfferDTO
{
  public function __construct(
    public readonly string $title,
    public readonly string $description,
    public readonly string $requirements,
    public readonly string $benefits,
    public readonly ?int $companyId,
    public readonly ?int $categoryId,
    public readonly ?int $locationId,
    public readonly ?int $contractTypeId,
    public readonly ?int $scheduleId,
    public readonly float $salary,
    public readonly string $salaryCurrency,
    public readonly string $address,
    public readonly string $department,
    public readonly string $province,
    public readonly string $country,
    public readonly string $publicationDate,
    public readonly ?array $attachments = null
  ) {}
}
