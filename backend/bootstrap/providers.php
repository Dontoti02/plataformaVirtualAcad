<?php

return [
    App\Providers\AppServiceProvider::class,
    App\Providers\TenancyServiceProvider::class,
    App\Providers\DOSpacesStorageServiceProvider::class,
    Modules\Tenant\Modules\JobOpportunities\JobOpportunityServiceProvider::class,
];
