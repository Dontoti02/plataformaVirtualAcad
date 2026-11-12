<?php
namespace Modules\Tenant\Modules\JobOpportunities\JobOffer\Infrastructure\Persistence\Eloquent;

use Carbon\Carbon;
use Modules\Tenant\Modules\JobOpportunities\JobOffer\Application\DTOs\CreateJobOfferDTO;
use Modules\Tenant\Modules\JobOpportunities\JobOffer\Domain\Repositories\UpdateJobOfferRepositoryInterface;
use Modules\Tenant\Modules\JobOpportunities\Shared\Domain\Models\JobOffer;
use Modules\Tenant\Modules\JobOpportunities\Shared\Domain\Utils\CreateSlugJobOffer;

class UpdateJobOfferRepository implements UpdateJobOfferRepositoryInterface
{
  public function update(int $id, CreateJobOfferDTO $form): void
  {
    $jobOffer = JobOffer::find($id);
    $slug = CreateSlugJobOffer::create($form->title);
    $jobOffer->title = $form->title;
    $jobOffer->slug = $slug;
    $jobOffer->description = $form->description;
    $jobOffer->requirements = $form->requirements;
    $jobOffer->benefits = $form->benefits;
    $jobOffer->company_id = $form->companyId;
    $jobOffer->category_id = $form->categoryId;
    $jobOffer->location_id = $form->locationId;
    $jobOffer->contract_type_id = $form->contractTypeId;
    $jobOffer->work_schedule_id = $form->scheduleId;
    $jobOffer->salary = $form->salary;
    $jobOffer->salary_currency = $form->salaryCurrency;
    $jobOffer->address = $form->address;
    $jobOffer->department = $form->department;
    $jobOffer->province = $form->province;
    $jobOffer->country = $form->country;
    $jobOffer->publication_date = $form->publicationDate ?? Carbon::now();
    $jobOffer->save();
  }
  
}