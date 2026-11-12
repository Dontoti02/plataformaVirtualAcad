<?php
namespace Modules\Tenant\Modules\JobOpportunities\JobOffer\Interfaces\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Tenant\Modules\JobOpportunities\JobOffer\Application\Requests\UpdateProfileCompanyRequest;
use Modules\Tenant\Modules\JobOpportunities\JobOffer\Application\UseCases\CompanyUseCase;

class CompanyController extends Controller
{
  public function list() {
    return CompanyUseCase::list();
  }
  public function profile(Request $request) {
    return CompanyUseCase::profile($request);
  }
  public function updateProfile(UpdateProfileCompanyRequest $request) {
    return CompanyUseCase::updateProfile($request->validated());
  }
  public function uploadLogo(Request $request) {
    $request->validate([
      'file' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
    ]);
    if (!$request->hasFile('file')) {
      return response()->json(['error' => 'Logo es requerido.'], 400);
    }
    if (!$request->file('file')->isValid()) {
      return response()->json(['error' => 'Formato de logo es inválido.'], 400);
    }
    return CompanyUseCase::uploadLogo($request);
  }
  public function deleteLogo() {
    return CompanyUseCase::deleteLogo();
  }
  public function verifyCompany($id) {
    return CompanyUseCase::verifyCompany($id);
  }

  public function unverifyCompany($id) {
    return CompanyUseCase::unverifyCompany($id);
  }
  public function deleteCompany($id) {
    return CompanyUseCase::deleteCompany($id);
  }
  
}
