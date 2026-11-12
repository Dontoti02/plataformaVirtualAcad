<?php
namespace Modules\Tenant\Modules\JobOpportunities\MasterTable\Enums;

use Modules\Tenant\Modules\JobOpportunities\Shared\Domain\Models\JobContractType;
use Modules\Tenant\Modules\JobOpportunities\Shared\Domain\Models\JobLocation;
use Modules\Tenant\Modules\JobOpportunities\Shared\Domain\Models\JobOfferCategory;
use Modules\Tenant\Modules\JobOpportunities\Shared\Domain\Models\JobWorkSchedule;

class MasterTablesEnum
{
  public const MAP = [
    'work_schedule' => JobWorkSchedule::class,
    'category' => JobOfferCategory::class,
    'location' => JobLocation::class,
    'contract_type' => JobContractType::class,
  ];
}
