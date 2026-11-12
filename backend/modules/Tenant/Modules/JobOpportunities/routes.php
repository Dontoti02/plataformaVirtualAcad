<?php

use Illuminate\Support\Facades\Route;
use Modules\Tenant\Middleware\AuthTenantMiddleware;
use Modules\Tenant\Middleware\CheckInstitutionModuleMiddleware;
use Modules\Tenant\Middleware\DomainTenantMiddleware;
use Modules\Tenant\Middleware\SubscriptionTenantMiddleware;
use Modules\Tenant\Modules\JobOpportunities\Applicants\Controllers\ApplicantController;
use Modules\Tenant\Modules\JobOpportunities\Company\Interfaces\Http\Controllers\RegisterCompanyController;
use Modules\Tenant\Modules\JobOpportunities\JobOffer\Interfaces\Http\Controllers\ChangeOfferStatusController;
use Modules\Tenant\Modules\JobOpportunities\JobOffer\Interfaces\Http\Controllers\CompanyController;
use Modules\Tenant\Modules\JobOpportunities\JobOffer\Interfaces\Http\Controllers\CreateJobOfferController;
use Modules\Tenant\Modules\JobOpportunities\JobOffer\Interfaces\Http\Controllers\DeleteJobOfferController;
use Modules\Tenant\Modules\JobOpportunities\JobOffer\Interfaces\Http\Controllers\FindJobOfferController;
use Modules\Tenant\Modules\JobOpportunities\JobOffer\Interfaces\Http\Controllers\JobOfferFiltersController;
use Modules\Tenant\Modules\JobOpportunities\JobOffer\Interfaces\Http\Controllers\PublicJobOffersController;
use Modules\Tenant\Modules\JobOpportunities\JobOffer\Interfaces\Http\Controllers\SearchJobOffersController;
use Modules\Tenant\Modules\JobOpportunities\JobOffer\Interfaces\Http\Controllers\UpdateJobOfferController;
use Modules\Tenant\Modules\JobOpportunities\MasterTable\Controllers\MasterTableController;

#rutas publicas
Route::group(['middleware' => [
  DomainTenantMiddleware::class,
  SubscriptionTenantMiddleware::class,
  CheckInstitutionModuleMiddleware::class.':bolsa_laboral',
]], function () {

  Route::prefix('job-opportunities')
    ->group(function () {
      Route::prefix('public')->group(function () {
        Route::prefix('companies')->group(function () {
          Route::post('register', [RegisterCompanyController::class, '__invoke']);
          Route::get('', [CompanyController::class, 'list']);
        });
         Route::prefix('offers')
          ->group(function () {
            Route::get('filters', [PublicJobOffersController::class, 'filters']);
            Route::post('list', [PublicJobOffersController::class, 'list']);
            Route::get('slug/{slug}', [PublicJobOffersController::class, 'findSlug']);
          });
      });
     
    });
});

#rutas privadas
Route::group(['middleware' => [
  DomainTenantMiddleware::class,
  AuthTenantMiddleware::class,
  SubscriptionTenantMiddleware::class,
    CheckInstitutionModuleMiddleware::class.':bolsa_laboral',
]], function () {
  Route::prefix('job-opportunities')
    ->group(function () {
      Route::prefix('offers')
        ->group(function () {
          Route::get('filters', [JobOfferFiltersController::class, '__invoke']);
          Route::post('list', [SearchJobOffersController::class, '__invoke']);
          Route::post('create', [CreateJobOfferController::class, '__invoke']);
          Route::get('find', [FindJobOfferController::class, '__invoke']);
          Route::put('update/{id}', [UpdateJobOfferController::class, '__invoke']);
          Route::delete('delete/{id}', [DeleteJobOfferController::class, '__invoke']);
          Route::put('change-state', ChangeOfferStatusController::class);
        });
    })->group(function () {
      Route::controller(ApplicantController::class)
        ->prefix('applicants')
        ->group(function () {
          Route::get('check-offer/{slug}','checkOffer');
          Route::post('upload-cv', 'uploadCV');
          Route::post('apply-offer', 'applyOffer');
          Route::get('my-applications', 'myApplications');
          Route::delete('cancel/{id}', 'cancelApplication');
          Route::get('my-cvs','myCvs');
          Route::delete('my-cvs/{id}','deleteMyCv');
          Route::get('by-offer/filters', 'applicationsByOfferFilters');
          Route::get('by-offer', 'applicationsByOffer');
          Route::put('set-state/{id}', 'setState');
        });
    })->group(function () {
      Route::controller(CompanyController::class)
        ->prefix('companies')
        ->group(function () {
          Route::get('', 'list');
          Route::get('profile', 'profile');
          Route::put('profile', 'updateProfile');
          Route::post('upload-logo', 'uploadLogo');
          Route::delete('delete-logo', 'deleteLogo');
          Route::put('verify/{id}', 'verifyCompany');
          Route::put('unverify/{id}', 'unverifyCompany');
          Route::delete('delete/{id}', 'deleteCompany');
        });
    })->group(function () {
      Route::controller(MasterTableController::class)
        ->prefix('master-table')
        ->group(function () {
          Route::get('{table}', 'index');
          Route::post('{table}', 'store');
          Route::put('{table}/{id}', 'update');
          Route::delete('{table}/{id}', 'destroy');
        });
    });
});
