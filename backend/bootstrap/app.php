<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;
use Modules\Shared\Console\CentralSyncCommand;
use Modules\Shared\Console\InstitutionAdminPasswordCommand;
use Modules\Shared\Console\InstitutionDataReportCommand;
use Modules\Shared\Console\InstitutionInstallCommand;
use Modules\Shared\Console\InstitutionPurgePeriodCommand;
use Modules\Shared\Console\InstitutionUndoImportCommand;
use Modules\Shared\Console\InstitutionUpgradeCommand;
use Modules\Shared\Console\InstitutionWipeCommand;
use Modules\Shared\Middleware\Cors;
use Modules\Tenant\Middleware\InitializeTenancyByRequestDomain;
use Modules\Tenant\Middleware\InitializeTenancySingle;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        // Sin esto, routes/console.php no se carga y NINGUNA tarea programada
        // llega a ejecutarse, ni siquiera la sincronización nocturna.
        commands: __DIR__.'/../routes/console.php',
        apiPrefix: 'api',
        then: function () {
            // En `single` el tenant sale del .env: no se lee la cabecera
            // X-subdomain ni se consulta la BD central.
            $tenantMiddleware = config('tenancy.deploy_mode') === 'single'
                ? InitializeTenancySingle::class
                : InitializeTenancyByRequestDomain::class;

            // El panel de administración central no existe en un VPS por
            // institución: sus rutas solo se registran en el modo compartido.
            if (config('tenancy.deploy_mode') !== 'single') {
                Route::prefix('api/admin')
                    ->middleware('api')
                    ->group(base_path('modules/Admin/routes.php'));
            }

            // para tenant
            Route::prefix('api/tenant')
                ->middleware('api')
                ->middleware($tenantMiddleware)
                ->group(base_path('modules/Tenant/routes.php'));

            // Salud: la consulta el contenedor, el despliegue y el panel.
            Route::prefix('api')
                ->middleware('api')
                ->group(base_path('routes/health.php'));
        }
    )
    ->withCommands([
        CentralSyncCommand::class,
        InstitutionAdminPasswordCommand::class,
        InstitutionDataReportCommand::class,
        InstitutionInstallCommand::class,
        InstitutionPurgePeriodCommand::class,
        InstitutionUndoImportCommand::class,
        InstitutionUpgradeCommand::class,
        InstitutionWipeCommand::class,
    ])
    ->withMiddleware(function (Middleware $middleware) {
        return $middleware->append(Cors::class);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    ->create();
