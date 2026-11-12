<?php

namespace Modules\Tenant\Modules\JobOpportunities\JobOffer\Application\Formatters;

use Illuminate\Pagination\LengthAwarePaginator;

class JobOfferPublicFormatter
{
  public static function formatPaginated(LengthAwarePaginator $paginator): array
  {
    $data = $paginator->toArray();
    return [
      'items' => array_map(function ($item) {
        return [
          'id' => $item['id'],
          'title' => $item['title'],
          'slug' => $item['slug'],
          'description' => $item['description'],
          'requirements' => $item['requirements'],
          'benefits' => $item['benefits'],
          'companyId' => $item['company']['id'] ?? null,
          'companyName' => $item['company']['name'] ?? null,
          'categoryId' => $item['category']['id'] ?? null,
          'categoryName' => $item['category']['name'] ?? null,
          'locationId' => $item['location']['id'] ?? null,
          'locationName' => $item['location']['name'] ?? null,
          'contractTypeId' => $item['contract_type']['id'] ?? null,
          'contractTypeName' => $item['contract_type']['name'] ?? null,
          'scheduleId' => $item['schedule']['id'] ?? null,
          'scheduleName' => $item['schedule']['name'] ?? null,
          'salary' => $item['salary'],
          'salaryCurrency' => $item['salary_currency'],
          'address' => $item['address'],
          'department' => $item['department'],
          'province' => $item['province'],
          'country' => $item['country'],
          'publicationDate' => $item['publication_date'],
          'deadline' => $item['deadline'],
        ];
      }, $data['data']),
      'pagination' => [
        'totalItems' => $data['total'],
        'currentPage' => $data['current_page'],
        'lastPage' => $data['last_page'],
        'itemsPerPage' => $data['per_page'],
      ]
    ];
  }
}