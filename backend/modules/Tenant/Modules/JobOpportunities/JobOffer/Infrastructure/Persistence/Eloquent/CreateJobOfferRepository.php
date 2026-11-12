<?php
namespace Modules\Tenant\Modules\JobOpportunities\JobOffer\Infrastructure\Persistence\Eloquent;

use Carbon\Carbon;
use Modules\Tenant\Modules\JobOpportunities\JobOffer\Application\DTOs\CreateJobOfferDTO;
use Modules\Tenant\Modules\JobOpportunities\JobOffer\Application\Enums\JobOfferStateEnum;
use Modules\Tenant\Modules\JobOpportunities\JobOffer\Domain\Repositories\CreateJobOfferRepositoryInterface;
use Modules\Tenant\Modules\JobOpportunities\Shared\Domain\Models\JobOffer;
use Modules\Tenant\Modules\JobOpportunities\Shared\Domain\Models\JobOfferState;
use Modules\Tenant\Modules\JobOpportunities\Shared\Domain\Utils\CreateSlugJobOffer;

class CreateJobOfferRepository implements CreateJobOfferRepositoryInterface
{
  public function create(CreateJobOfferDTO $form): void
  {
    $activeStateKey = JobOfferStateEnum::ACTIVE;
    $jobOffer = new JobOffer();
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
    
    if ($form->attachments) {
      foreach ($form->attachments as $attachment) {
        // Assuming $attachment is a file path or URL
      }
    }

    $initialState = JobOfferState::where('key', $activeStateKey)->first();

    $jobOffer->state_id = $initialState->id;
    $jobOffer->save();
    $jobOffer->states()->attach($initialState->id, [
      'created_at' => Carbon::now(),
      'updated_at' => Carbon::now(),
    ]);
  }
  
}