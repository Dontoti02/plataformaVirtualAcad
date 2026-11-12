<?php

namespace Modules\Tenant\Modules\JobOpportunities\JobOffer\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Tenant\Modules\JobOpportunities\JobOffer\Application\UseCases\ChangeOfferStatusUseCase;
use Modules\Tenant\Modules\JobOpportunities\JobOffer\Application\UseCases\CreateJobOfferUseCase;
use Modules\Tenant\Modules\JobOpportunities\JobOffer\Application\UseCases\DeleteJobOfferUseCase;
use Modules\Tenant\Modules\JobOpportunities\JobOffer\Application\UseCases\FiltersJobOffersUseCase;
use Modules\Tenant\Modules\JobOpportunities\JobOffer\Application\UseCases\FindJobOfferUseCase;
use Modules\Tenant\Modules\JobOpportunities\JobOffer\Application\UseCases\SearchJobOffersUseCase;
use Modules\Tenant\Modules\JobOpportunities\JobOffer\Application\UseCases\UpdateobOfferUseCase;
use Modules\Tenant\Modules\JobOpportunities\JobOffer\Infrastructure\Persistence\Eloquent\CreateJobOfferRepository;
use Modules\Tenant\Modules\JobOpportunities\JobOffer\Infrastructure\Persistence\Eloquent\EloquentJobOfferRepository;
use Modules\Tenant\Modules\JobOpportunities\JobOffer\Infrastructure\Persistence\Eloquent\UpdateJobOfferRepository;

class JobOfferServiceProvider extends ServiceProvider
{
  public function register(): void
  {
    $this->app->bind(FiltersJobOffersUseCase::class, function ($app) {
      return new FiltersJobOffersUseCase(
        $app->make(EloquentJobOfferRepository::class)
      );
    });

    $this->app->bind(SearchJobOffersUseCase::class, function ($app) {
      return new SearchJobOffersUseCase(
        $app->make(EloquentJobOfferRepository::class)
      );
    });

    $this->app->bind(CreateJobOfferUseCase::class, function ($app) {
      return new CreateJobOfferUseCase(
        $app->make(CreateJobOfferRepository::class)
      );
    });
    
     $this->app->bind(FindJobOfferUseCase::class, function ($app) {
      return new FindJobOfferUseCase(
        $app->make(EloquentJobOfferRepository::class)
      );
    });

    $this->app->bind(UpdateobOfferUseCase::class, function ($app) {
      return new UpdateobOfferUseCase(
        $app->make(UpdateJobOfferRepository::class)
      );
    });

    $this->app->bind(DeleteJobOfferUseCase::class, function ($app) {
      return new DeleteJobOfferUseCase(
        $app->make(EloquentJobOfferRepository::class)
      );
    });
    
    $this->app->bind(ChangeOfferStatusUseCase::class, function ($app) {
      return new ChangeOfferStatusUseCase(
        $app->make(EloquentJobOfferRepository::class)
      );
    });
    
  }

  public function boot(): void
  {
    //
  }
}
