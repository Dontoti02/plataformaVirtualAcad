<?php
namespace Modules\Tenant\Modules\JobOpportunities\JobOffer\Interfaces\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Tenant\Modules\JobOpportunities\JobOffer\Application\UseCases\PublicJobOffersUseCase;

class PublicJobOffersController extends Controller
{
  public function filters() {
    return PublicJobOffersUseCase::filters();
  }

  public function list(Request $request) {
    return PublicJobOffersUseCase::list($request);
  }

  public function findSlug(Request $request,$slug) {
    return PublicJobOffersUseCase::findSlug($request,$slug);
  }
}
