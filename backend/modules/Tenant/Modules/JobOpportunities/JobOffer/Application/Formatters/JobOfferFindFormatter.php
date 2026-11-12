<?php

namespace Modules\Tenant\Modules\JobOpportunities\JobOffer\Application\Formatters;

use Modules\Tenant\Modules\JobOpportunities\Shared\Domain\Models\JobOffer;

class JobOfferFindFormatter
{
  public static function format(JobOffer $item): array
  {
    return [
      'id' => $item->id,
      'title' => $item->title,
      'description' => $item->description,
      'requirements' => $item->requirements,
      'benefits' => $item->benefits,
      'companyId' => $item->company ? $item->company->id : null,
      'categoryId' => $item->category ? $item->category->id : null,
      'locationId' => $item->location ? $item->location->id : null,
      'contractTypeId' => $item->contractType ? $item->contractType->id : null,
      'scheduleId' => $item->schedule ? $item->schedule->id : null,
      'salary' => $item->salary,
      'salaryCurrency' => $item->salary_currency,
      'address' => $item->address,
      'department' => $item->department,
      'province' => $item->province,
      'country' => $item->country,
      'publicationDate' => $item->publication_date?->format('d-m-Y H:i:s'),
      'deadline' => $item->gdeadline,
      'state' => [
        'id' => $item->state->id,
        'key' => $item->state->key,
        'name' => $item->state->name,
      ]
    ];
  }
}
