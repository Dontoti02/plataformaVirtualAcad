<?php

namespace Modules\Tenant\Modules\Enroll\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Tenant\Modules\Enroll\UseCases\EnrollUseCases;

class EnrollController extends Controller
{
  public function list(Request $request)
  {
    return EnrollUseCases::list($request);
  }
  public function getFormsData()
  {
    return EnrollUseCases::getFormsData();
  }
  public function SearchStudent(Request $request)
  {
    return EnrollUseCases::SearchStudent($request);
  }
  public function validateDNI(Request $request)
  {
    return EnrollUseCases::validateDNI($request);
  }
  public function enrollRegularStudent(Request $request)
  {
    return EnrollUseCases::enrollRegularStudent($request);
  }
  public function enrollNewStudent(Request $request)
  {
    return EnrollUseCases::enrollNewStudent($request);
  }
  public function validateFamilyDNI(Request $request)
  {
    return EnrollUseCases::validateFamilyDNI($request);
  }
  public function validateEnrollment(Request $request)
  {
    return EnrollUseCases::validateEnrollment($request);
  }
  public function delete(int $id)
  {
    return EnrollUseCases::delete($id);
  }
  public function get(int $id)
  {
    return EnrollUseCases::get($id);
  }
  public function updateEnroll(int $id, Request $request)
  {
    return EnrollUseCases::updateEnroll($id, $request);
  }

  public function getValidationsForEnrollment()
  {
    return EnrollUseCases::getValidationsForEnrollment();
  }
}
