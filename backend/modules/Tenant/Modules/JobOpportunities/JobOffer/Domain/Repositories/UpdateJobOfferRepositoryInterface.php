<?php
namespace Modules\Tenant\Modules\JobOpportunities\JobOffer\Domain\Repositories;

use Modules\Tenant\Modules\JobOpportunities\JobOffer\Application\DTOs\CreateJobOfferDTO;

interface UpdateJobOfferRepositoryInterface
{
  public function update(int $id,CreateJobOfferDTO $form) : void;
}