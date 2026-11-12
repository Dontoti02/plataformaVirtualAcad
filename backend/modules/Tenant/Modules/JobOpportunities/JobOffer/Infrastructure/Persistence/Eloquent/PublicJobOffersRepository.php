<?php
namespace Modules\Tenant\Modules\JobOpportunities\JobOffer\Infrastructure\Persistence\Eloquent;

use Illuminate\Http\Request;
use Modules\Shared\Helpers\SessionManager;
use Modules\Tenant\Modules\JobOpportunities\JobOffer\Application\Enums\FilterOfferEnum;
use Modules\Tenant\Modules\JobOpportunities\JobOffer\Application\Enums\JobOfferStateEnum;
use Modules\Tenant\Modules\JobOpportunities\JobOffer\Application\Helpers\JobOfferTmpSession;
use Modules\Tenant\Modules\JobOpportunities\Shared\Domain\Models\JobContractType;
use Modules\Tenant\Modules\JobOpportunities\Shared\Domain\Models\JobLocation;
use Modules\Tenant\Modules\JobOpportunities\Shared\Domain\Models\JobOffer;
use Modules\Tenant\Modules\JobOpportunities\Shared\Domain\Models\JobOfferCategory;
use Modules\Tenant\Modules\JobOpportunities\Shared\Domain\Models\JobWorkSchedule;
use Modules\Tenant\Modules\User\Models\User;

class PublicJobOffersRepository {
  
  public static function filters() : array {
    return [
      'orderBy' => FilterOfferEnum::ORDER_BY,
      'dateFilters' => FilterOfferEnum::DATE_FILTERS,
      'categories' => JobOfferCategory::select('id', 'name')->get(),
      'salaryRanges' => FilterOfferEnum::SALARY_RANGES,
      'schedules' => JobWorkSchedule::select('id', 'name')->get(),
      'locations' => JobLocation::select('id','name')->get(),
      'contractTypes' => JobContractType::select('id', 'name')->get(),
    ];
  }

  
  public static function list(Request $filters) 
  {
    $query = JobOffer::query()->with([
      'company', 
      'category', 
      'schedule',
      'location',
      'contractType',
    ]);

    if ($filters->companyId) $query->where('company_id', $filters->companyId);
    if ($filters->categoryId) $query->where('category_id', $filters->categoryId);
    if ($filters->scheduleId) $query->where('work_schedule_id', $filters->scheduleId);
    if ($filters->contractTypeId) $query->where('contract_type_id', $filters->contractTypeId);
    if ($filters->locationId) $query->where('location_id', $filters->locationId);

    if ($filters->dateFilter) {
      $map = collect(FilterOfferEnum::DATE_FILTERS);
      $item = $map->filter(fn($value) => $value['id'] === $filters->dateFilter)->first();
      if (isset($item)) {
        $query->where('publication_date', '>=', now()->subDays($item['days']));
      }
    }

    if ($filters->salary) {
      $salaryRange = $filters->salary;
      if (str_starts_with($salaryRange, '-')) {
        $max = (int) ltrim($salaryRange, '-');
        $query->where('salary', '<', $max);
      } elseif (str_starts_with($salaryRange, '+')) {
        $min = (int) ltrim($salaryRange, '+');
        $query->where('salary', '>=', $min);
      }
    }

    if ($filters->search) {
      $query->where('title', 'like', '%' . $filters->search . '%');
    }

    if ($filters->orderBy === 'salario') {
      $query->orderBy('salary', 'desc');
    } else {
      $query->orderBy('publication_date', 'desc');
    }

    $query->where('state_id', JobOfferStateEnum::ACTIVE_ID);

    return $query->paginate($filters->perPage, ['*'], 'page', $filters->page);
  }

  public static function findSlug(Request $request,string $slug) 
  {
    $session = JobOfferTmpSession::get($request);

    $find = JobOffer::query()
      ->with([
        'company', 
        'category', 
        'schedule',
        'location',
        'contractType',
        'applications'
      ])
      ->where('slug', $slug)
      ->first();
    
    if (!$find) {
      return null;
    }

    $isLoggedIn = false;
    $alreadyPostulated = false;
    if ($session) {
      $isLoggedIn = true;
      $alreadyPostulated = $find->applications()->hasPostulated($session?->id ?? null);
    }
    return [
      'company' => [
        'name' => $find->company->name,
        'logo' => $find->company->logo,
        'address' => $find->company->address,
        'description' => $find->company->description,
        'isVerified' => $find->company->is_verified,
        'website' => $find->company->website,
      ],
      'slug' => $find->slug,
      'categoryName' => $find->category?->name ?? '',
      'locationName' => $find->location?->name ?? '',
      'contractTypeName' => $find->contractType?->name ?? '',
      'scheduleName' => $find->schedule?->name ?? '',
      'title' => $find->title,
      'description' => $find->description,
      'requirements' => $find->requirements,
      'benefits' => $find->benefits,
      'salary' => $find->salary,
      'salaryCurrency' => $find->salary_currency,
      'publicationDate' => $find->publication_date,
      'isActive' => $find->state_id === JobOfferStateEnum::ACTIVE_ID,
      'isLoggedIn' => $isLoggedIn,
      'alreadyPostulated' => $alreadyPostulated
    ];

  }
}