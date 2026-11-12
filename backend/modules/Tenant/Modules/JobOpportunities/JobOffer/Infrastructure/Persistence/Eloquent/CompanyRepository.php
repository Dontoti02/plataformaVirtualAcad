<?php
namespace Modules\Tenant\Modules\JobOpportunities\JobOffer\Infrastructure\Persistence\Eloquent;

use Modules\Shared\Helpers\SessionManager;
use Modules\Tenant\Modules\JobOpportunities\Shared\Domain\Models\JobCompany;
use Modules\Tenant\Modules\User\Enums\RolTenant;
use Modules\Tenant\Modules\User\Models\User;

class CompanyRepository {

  public static function list() 
  {
    $companies = JobCompany::orderBy('created_at', 'desc')
      ->get();
    return $companies->map(function ($company) {
      $totalOffers = $company->offers()->count();
      return [
        'id' => (int) $company->id,
        'name' => (string) $company->name,
        'email' => (string) $company->email,
        'ruc' => (string) $company->ruc,
        'phone' => (string) $company->phone,
        'address' => (string) $company->address,
        'mailbox' => $company->mailbox !== null ? (string) $company->mailbox : null,
        'website' => $company->website !== null ? (string) $company->website : null,
        'description' => $company->description !== null ? (string) $company->description : null,
        'logo' => $company->logo !== null ? (string) $company->logo : null,
        'isVerified' => (bool) $company->is_verified,
        'totalOffers' => (int) $totalOffers,
      ];
    });
    return $companies;
  }

  public static function profile() 
  {
    $session = SessionManager::get();

    $user = User::find($session->id);
    if (!$user) {
      throw new \Exception('User not found');
    }
    
    if (!$user->hasRole(RolTenant::COMPANY)) {
      throw new \Exception('El usuario no tiene el rol de empresa');
    }

    $company = $user->company;

    if (!$company) {
      throw new \Exception('Empresa no encontrada para el usuario');
    }
    
    return [
      'id' => (int) $company->id,
      'name' => (string) $company->name,
      'email' => (string) $company->email,
      'ruc' => (string) $company->ruc,
      'phone' => (string) $company->phone,
      'address' => (string) $company->address,
      'mailbox' => $company->mailbox !== null ? (string) $company->mailbox : null,
      'website' => $company->website !== null ? (string) $company->website : null,
      'description' => $company->description !== null ? (string) $company->description : null,
      'logo' => $company->logo !== null ? (string) $company->logo : null,
      'isVerified' => (bool) $company->isVerified,
    ];
  }

  public static function updateProfile($request) 
  {
    $session = SessionManager::get();

    $user = User::find($session->id);
    if (!$user) {
      throw new \Exception('User not found');
    }
    
    if (!$user->hasRole(RolTenant::COMPANY)) {
      throw new \Exception('El usuario no tiene el rol de empresa');
    }

    $company = $user->company;

    if (!$company) {
      throw new \Exception('Empresa no encontrada para el usuario');
    }

    $company->fill($request);
    $company->save();

    return [
      'id' => (int) $company->id,
      'name' => (string) $company->name,
      'email' => (string) $company->email,
      'ruc' => (string) $company->ruc,
      'phone' => (string) $company->phone,
      'address' => (string) $company->address,
      'mailbox' => $company->mailbox !== null ? (string) $company->mailbox : null,
      'website' => $company->website !== null ? (string) $company->website : null,
      'description' => $company->description !== null ? (string) $company->description : null,
      'logo' => $company->logo !== null ? (string) $company->logo : null,
      'isVerified' => (bool) $company->isVerified,
    ];
  }
  
  public static function uploadLogo($request) 
  {
    $session = SessionManager::get();

    $user = User::find($session->id);
    if (!$user) {
      throw new \Exception('User not found');
    }
    
    if (!$user->hasRole(RolTenant::COMPANY)) {
      throw new \Exception('El usuario no tiene el rol de empresa');
    }

    $company = $user->company;

    if (!$company) {
      throw new \Exception('Empresa no encontrada para el usuario');
    }

    if ($request->hasFile('file')) {
      $company->logo = $request->file('file')->store('companies/logo', 'public');
      $company->save();
    }
    return $company->logo;
  }

  public static function deleteLogo() 
  {
    $session = SessionManager::get();

    $user = User::find($session->id);
    if (!$user) {
      throw new \Exception('User not found');
    }
    
    if (!$user->hasRole(RolTenant::COMPANY)) {
      throw new \Exception('El usuario no tiene el rol de empresa');
    }

    $company = $user->company;

    if (!$company) {
      throw new \Exception('Empresa no encontrada para el usuario');
    }

    $company->logo = null;
    $company->save();

    return [
      'message' => 'Logo eliminado correctamente',
    ];
  }

  public static function verifyCompany($id) 
  {
    $session = SessionManager::get();

    $user = User::find($session->id);
    if (!$user) {
      throw new \Exception('User not found');
    }
    
    if (!$user->hasRole(RolTenant::ADMINISTRADOR)) {
      throw new \Exception('El usuario no tiene el rol de administrador');
    }

    $company = JobCompany::find($id);
    if (!$company) {
      throw new \Exception('Empresa no encontrada');
    }
    if ($company->is_verified) {
      throw new \Exception('La empresa ya está verificada');
    }
    $company->is_verified = true;
    $company->save();
    return [
      'message' => 'Empresa verificada correctamente',
      'company' => $company,
    ];
  }
  public static function unverifyCompany($id) 
  {
    $session = SessionManager::get();

    $user = User::find($session->id);
    if (!$user) {
      throw new \Exception('User not found');
    }
    
    if (!$user->hasRole(RolTenant::ADMINISTRADOR)) {
      throw new \Exception('El usuario no tiene el rol de administrador');
    }

    $company = JobCompany::find($id);
    if (!$company) {
      throw new \Exception('Empresa no encontrada');
    }
    if (!$company->is_verified) {
      throw new \Exception('La empresa ya no está verificada');
    }
    $company->is_verified = false;
    $company->save();
    return [
      'message' => 'Empresa no verificada correctamente',
      'company' => $company,
    ];
  }
  public static function deleteCompany($id) 
  {
    $session = SessionManager::get();

    $user = User::find($session->id);
    if (!$user) {
      throw new \Exception('User not found');
    }
    
    if (!$user->hasRole(RolTenant::ADMINISTRADOR)) {
      throw new \Exception('El usuario no tiene el rol de administrador');
    }

    $company = JobCompany::find($id);
    if (!$company) {
      throw new \Exception('Empresa no encontrada');
    }
    
    $company->delete();
    
    return [
      'message' => 'Empresa eliminada correctamente',
    ];
  }
}