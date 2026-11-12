<?php
namespace Modules\Tenant\Modules\JobOpportunities\JobOffer\Infrastructure\Persistence\Eloquent;

use Illuminate\Contracts\Queue\Job;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Tenant\Modules\JobOpportunities\JobOffer\Application\DTOs\FiltersJobOfferDTO;
use Modules\Tenant\Modules\JobOpportunities\JobOffer\Application\DTOs\ListJobOffersFilterDTO;
use Modules\Tenant\Modules\JobOpportunities\JobOffer\Application\Enums\FilterOfferEnum;
use Modules\Tenant\Modules\JobOpportunities\JobOffer\Domain\Repositories\JobOfferRepositoryInterface;
use Modules\Tenant\Modules\JobOpportunities\Shared\Domain\Models\JobCompany;
use Modules\Tenant\Modules\JobOpportunities\Shared\Domain\Models\JobContractType;
use Modules\Tenant\Modules\JobOpportunities\Shared\Domain\Models\JobLocation;
use Modules\Tenant\Modules\JobOpportunities\Shared\Domain\Models\JobOffer;
use Modules\Tenant\Modules\JobOpportunities\Shared\Domain\Models\JobOfferCategory;
use Modules\Tenant\Modules\JobOpportunities\Shared\Domain\Models\JobWorkSchedule;

class EloquentJobOfferRepository implements JobOfferRepositoryInterface
{
  public function list(ListJobOffersFilterDTO $filter) : LengthAwarePaginator
  {
    $query = JobOffer::query()->with([
      'company', 
      'category', 
      'schedule',
      'location',
      'contractType',
      'state'
    ]);

    if ($filter->companyId) $query->where('company_id', $filter->companyId);
    if ($filter->categoryId) $query->where('category_id', $filter->categoryId);
    if ($filter->scheduleId) $query->where('work_schedule_id', $filter->scheduleId);
    if ($filter->contractTypeId) $query->where('contract_type_id', $filter->contractTypeId);
    if ($filter->locationId) $query->where('location_id', $filter->locationId);

    if ($filter->dateFilter) {
      $map = collect(FilterOfferEnum::DATE_FILTERS);
      $item = $map->filter(fn($value) => $value['id'] === $filter->dateFilter)->first();
      if (isset($item)) {
        $query->where('publication_date', '>=', now()->subDays($item['days']));
      }
    }

    if ($filter->salary) {
      $salaryRange = $filter->salary;
      if (str_starts_with($salaryRange, '-')) {
        $max = (int) ltrim($salaryRange, '-');
        $query->where('salary', '<', $max);
      } elseif (str_starts_with($salaryRange, '+')) {
        $min = (int) ltrim($salaryRange, '+');
        $query->where('salary', '>=', $min);
      }
    }

    if ($filter->search) {
      $query->where('title', 'like', '%' . $filter->search . '%');
    }

    if ($filter->orderBy === 'salary') {
      $query->orderBy('salary', 'desc');
    } else {
      $query->orderBy('publication_date', 'desc');
    }

    return $query->paginate($filter->perPage, ['*'], 'page', $filter->page);
  }

  public function filters(FiltersJobOfferDTO $filter) : array
  {
    $queryCompanies = JobCompany::query();

    if ($filter->companyId) {
      $queryCompanies = JobCompany::query()->where('id', $filter->companyId);
    }
    $departments = JobOffer::select('department')->distinct()->get();
    $provinces = JobOffer::select('province')->distinct()->get();
    return [
      'orderBy' => FilterOfferEnum::ORDER_BY,
      'dateFilters' => FilterOfferEnum::DATE_FILTERS,
      'categories' => JobOfferCategory::select('id', 'name')->get(),
      'salaryRanges' => FilterOfferEnum::SALARY_RANGES,
      'schedules' => JobWorkSchedule::select('id', 'name')->get(),
      'locations' => JobLocation::select('id','name')->get(),
      'contractTypes' => JobContractType::select('id', 'name')->get(),
      'provinces' => $provinces,
      'departments' => $departments,
      'companies' => $queryCompanies->select('id', 'name')->get(),
      'isAdmin' => $filter->isAdmin,
    ];
  }

  public function findById(string $id) : ?JobOffer
  {
    return JobOffer::with([
      'company', 
      'category', 
      'schedule',
      'location',
      'contractType',
    ])->find($id);
  }
  public function findBySlug(string $slug): ?JobOffer
  {
    return JobOffer::with([
      'company', 
      'category', 
      'schedule',
      'location',
      'contractType',
    ])->where('slug', $slug)->first();
  }

  public function delete(string $id): void
  {
    JobOffer::destroy($id);
  }

  public function changeStatus(JobOffer $offer, int $newStateId): void
  {
    $offer->states()->attach($newStateId, [
      'created_at' => now(),
      'updated_at' => now(),
    ]);
    $offer->state_id = $newStateId;
    $offer->save();
  }
}
