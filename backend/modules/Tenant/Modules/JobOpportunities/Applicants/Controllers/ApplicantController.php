<?php

namespace Modules\Tenant\Modules\JobOpportunities\Applicants\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Modules\Shared\Utils\Response;
use Modules\Tenant\Modules\JobOpportunities\Applicants\UseCases\ApplicantUseCase;

class ApplicantController
{
  public function checkOffer($slug)
  {
    return ApplicantUseCase::checkOffer($slug);
  }

  public function uploadCV(Request $request)
  {
    $validator = Validator::make($request->all(), [
      'file*' => 'required|file|mimes:pdf,doc,docx|max:8192',
    ]);

    if ($validator->fails()) {
      return Response::error($validator->errors()->first());
    }
    return ApplicantUseCase::uploadCV($request);
  }

  public function applyOffer(Request $request)
  {
    $validator = Validator::make($request->all(), [
      'offerId' => 'required|integer|exists:job_opportunity_offer,id',
      'cvId' => 'required|integer|exists:job_opportunity_user_cv,id',
    ]);

    if ($validator->fails()) {
      return Response::error($validator->errors()->first());
    }
    return ApplicantUseCase::applyOffer($request);
  }

  public function myApplications(Request $request)
  {
    return ApplicantUseCase::myApplications($request);
  }

  public function cancelApplication($id)
  {
    try {
      $validator = Validator::make(['id' => $id], [
        'id' => 'required|integer|exists:job_opportunity_applications,id',
      ]);

      if ($validator->fails()) {
        return Response::error($validator->errors()->first());
      }

      return ApplicantUseCase::cancelApplication($id);
    } catch (Exception $e) {
      return Response::error('Error al cancelar la postulación: ' . $e->getMessage());
    }
  }

  public function myCvs()
  {
    return ApplicantUseCase::myCvs();
  }

  public function deleteMyCv($id)
  {
    return ApplicantUseCase::deleteMyCv($id);
  }

  public function applicationsByOfferFilters(Request $request)
  {
    $offerId = $request->query('offerId', null);
    $companyId = $request->query('companyId', null);
    return ApplicantUseCase::applicationsByOfferFilters($offerId, $companyId);
  }

  public function applicationsByOffer(Request $request)
  {
    $offerId = $request->query('offerId');
    $companyId = $request->query('companyId', null);
    $validator = Validator::make(['offerId' => $offerId], [
      'offerId' => 'required|integer|exists:job_opportunity_offer,id',
    ]);
    if ($validator->fails()) {
      return Response::error($validator->errors()->first());
    }
    return ApplicantUseCase::applicationsByOffer($offerId, $companyId);
  }

  public function setState(Request $request, $id)
  {
    $validator = Validator::make($request->all(), [
      'state' => 'required|string|in:accepted,rejected',
    ]);

    if ($validator->fails()) {
      return Response::error($validator->errors()->first());
    }

    return ApplicantUseCase::setState($id, $request->all());
  }
}
