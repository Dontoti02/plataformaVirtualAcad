<?php

namespace Modules\Tenant\Modules\JobOpportunities\JobOffer\Application\UseCases;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Shared\Utils\Response;
use Modules\Tenant\Modules\JobOpportunities\JobOffer\Infrastructure\Persistence\Eloquent\CompanyRepository;

class CompanyUseCase
{
  static function list() {
    try {
      DB::beginTransaction();
      $result = CompanyRepository::list();
      DB::commit();
      return Response::success($result, 'compañias obtenidas correctamente.');
    } catch (Exception $e) {
      DB::rollBack();
      return Response::error($e->getMessage());
    }
  }

  static function profile(Request $request) {
    try {
      DB::beginTransaction();
      $result = CompanyRepository::profile($request);
      DB::commit();
      return Response::success($result, 'perfil de empresa obtenido correctamente.');
    } catch (Exception $e) {
      DB::rollBack();
      return Response::error($e->getMessage());
    }
  }

  static function updateProfile(array $request) {
    try {
      DB::beginTransaction();
      $result = CompanyRepository::updateProfile($request);
      DB::commit();
      return Response::success($result, 'perfil de empresa actualizado correctamente.');
    } catch (Exception $e) {
      DB::rollBack();
      Log::error('Error : ' . $e->getMessage(), [
        'request' => $request,
      ]);
      return Response::error($e->getMessage());
    }
  }

  static function uploadLogo(Request $request) {
    try {
      DB::beginTransaction();
      $result = CompanyRepository::uploadLogo($request);
      DB::commit();
      return Response::success($result, 'logo actualizado correctamente.');
    } catch (Exception $e) {
      DB::rollBack();
      return Response::error($e->getMessage());
    }
  }

  static function deleteLogo() {
    try {
      DB::beginTransaction();
      $result = CompanyRepository::deleteLogo();
      DB::commit();
      return Response::success($result, 'logo eliminado correctamente.');
    } catch (Exception $e) {
      DB::rollBack();
      return Response::error($e->getMessage());
    }
  }

  static function verifyCompany(int $id) {
    try {
      DB::beginTransaction();
      $result = CompanyRepository::verifyCompany($id);
      DB::commit();
      return Response::success($result, 'compañia verificada correctamente.');
    } catch (Exception $e) {
      DB::rollBack();
      return Response::error($e->getMessage());
    }
  }
  static function unverifyCompany(int $id) {
    try {
      DB::beginTransaction();
      $result = CompanyRepository::unverifyCompany($id);
      DB::commit();
      return Response::success($result, 'compañia no verificada correctamente.');
    } catch (Exception $e) {
      DB::rollBack();
      return Response::error($e->getMessage());
    }
  }
  static function deleteCompany(int $id) {
    try {
      DB::beginTransaction();
      $result = CompanyRepository::deleteCompany($id);
      DB::commit();
      return Response::success($result, 'compañia eliminada correctamente.');
    } catch (Exception $e) {
      DB::rollBack();
      return Response::error($e->getMessage());
    }
  }
}
