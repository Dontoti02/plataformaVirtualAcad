<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Shared\Health\HealthCheck;

/**
 * Endpoint de salud, consumido por:
 *   - el healthcheck del contenedor php
 *   - la fase POSTFLIGHT del orquestador
 *   - el heartbeat que cada VPS envía al panel
 *
 * Deliberadamente sin autenticación pero sin filtrar nada sensible:
 * devuelve estados, no configuración.
 */
Route::get('health', function () {
    $report = HealthCheck::run();

    return response()->json($report, $report['status'] === 'ok' ? 200 : 503);
});
