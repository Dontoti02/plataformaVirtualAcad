<?php

use Illuminate\Support\Facades\Route;
use Modules\Admin\Controllers\AuthController;
use Modules\Admin\Controllers\InstitutionController;
use Modules\Admin\Controllers\InstitutionModuleController;
use Modules\Admin\Controllers\SystemConfigurationController;
use Modules\Admin\Controllers\UserController;
use Modules\Admin\Middleware\AuthAdminMiddleware;
use Modules\Tenant\Modules\Treasury\Controllers\PaymentsController;

# rutas publicas
Route::group([], function () {

	Route::controller(SystemConfigurationController::class)
		->prefix('system_configuration')
		->group(function () {
			Route::get('general', 'general');
      Route::get('boleta','boleta');
		});

    Route::controller(PaymentsController::class)
		->prefix('boleta')
		->group(function () {
      Route::get('demo','boleta');
		});

	Route::controller(AuthController::class)
		->prefix('auth')
		->group(function () {
			Route::post('login', 'login');
			Route::put('reset/password', 'resetPassword');
			Route::get('check/reset/password', 'checkResetPassword');
			Route::put('change/password', 'changePassword');
		});
});

# rutas privadas
Route::group(['middleware' => AuthAdminMiddleware::class], function () {

	Route::controller(AuthController::class)
		->prefix('auth')
		->group(function () {
			Route::put('change/rol/{rol_id}', 'changeRol');
		});

	Route::controller(UserController::class)
		->prefix('user')
		->group(function () {
			Route::get('params', 'params');
			Route::post('list', 'list');
			Route::post('create', 'create');
			Route::put('update/{id}', 'update');
			Route::put('change/password/{id}', 'changePassword');
			Route::post('change/photo/{id}', 'changePhoto');
			Route::delete('delete/photo/{id}', 'deletePhoto');
			Route::put('disable/{id}', 'disable');
			Route::delete('delete/{id}', 'delete');
		});

	Route::controller(SystemConfigurationController::class)
		->prefix('system_configuration')
		->group(function () {
			Route::get('list', 'list');
			Route::post('update/{key}', 'update');
		});

	Route::controller(InstitutionController::class)
		->prefix('institution')
		->group(function () {
			Route::get('config', 'config');
			Route::post('list', 'list');
			Route::get('{id}', 'get');
			Route::post('create', 'create');
			Route::put('update/{id}', 'update');
			Route::put('disable/{id}', 'disable');
			Route::put('subscription/{id}', 'subscription');
			Route::get('exists_subdomain/{subdomain}', 'existSubdomain');
			Route::post('create_user', 'createUser');
			Route::get('detail/{id}', 'detail');
			Route::post('resize_storage', 'resizeStorage');
			Route::post('update_subdomain', 'updateSubdomain');
			Route::delete('delete/{id}', 'delete');
		});

  Route::controller(InstitutionModuleController::class)
		->prefix('institution-modules')
		->group(function () {
			Route::get('list/{id}', 'index');
      Route::post('toggle/{institutionId}', 'toggle');
      Route::post('update-dates/{institutionId}','updateDates');
    });
});
