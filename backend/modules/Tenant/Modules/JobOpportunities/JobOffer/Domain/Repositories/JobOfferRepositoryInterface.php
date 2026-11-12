<?php
namespace Modules\Tenant\Modules\JobOpportunities\JobOffer\Domain\Repositories;

use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Tenant\Modules\JobOpportunities\JobOffer\Application\DTOs\FiltersJobOfferDTO;
use Modules\Tenant\Modules\JobOpportunities\JobOffer\Application\DTOs\ListJobOffersFilterDTO;
use Modules\Tenant\Modules\JobOpportunities\Shared\Domain\Models\JobOffer;

interface JobOfferRepositoryInterface
{
  public function filters(FiltersJobOfferDTO $filter) : array;

  public function list(ListJobOffersFilterDTO $filter) : LengthAwarePaginator;

  public function findById(string $id): ?JobOffer;
  
  public function findBySlug(string $slug): ?JobOffer;
  
  public function delete(string $id): void;

  public function changeStatus(JobOffer $id, int $status): void;
}