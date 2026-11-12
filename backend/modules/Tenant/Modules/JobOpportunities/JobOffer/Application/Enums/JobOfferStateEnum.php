<?php

namespace Modules\Tenant\Modules\JobOpportunities\JobOffer\Application\Enums;

class JobOfferStateEnum
{
  const DRAFT = 'draft';
  const ACTIVE = 'active';
  const FINISHED = 'finished';
  const SUSPENDED = 'suspended';
  const CANCELED = 'canceled';

  const DRAFT_ID = 1;
  const ACTIVE_ID = 2;
  const FINISHED_ID = 3;
  const SUSPENDED_ID = 4;
  const CANCELED_ID = 5;
}
