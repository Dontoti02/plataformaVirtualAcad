<?php
namespace Modules\Tenant\Modules\JobOpportunities\Company\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Tenant\Modules\JobOpportunities\Company\Application\Actions\RegisterCompanyAction;
use Modules\Tenant\Modules\JobOpportunities\Company\Application\UseCases\RegisterCompanyUseCase;

class CompanyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(RegisterCompanyUseCase::class, function ($app) {
            return new RegisterCompanyUseCase(
                $app->make(RegisterCompanyAction::class)
            );
        });
    }

    public function boot(): void
    {
        //
    }
}


