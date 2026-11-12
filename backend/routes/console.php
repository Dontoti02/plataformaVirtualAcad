<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Tareas programadas
|--------------------------------------------------------------------------
|
| Antes no existía ninguna y nadie purgaba nada: en el servidor compartido se
| acumularon 1,58 GB de `failed_jobs`. En un VPS, con MySQL, archivos y backups
| en el mismo disco, eso tumba la institución.
|
*/

Schedule::command('queue:prune-failed --hours=168')->daily();
Schedule::command('queue:prune-batches --hours=168')->daily();

Schedule::command('central:sync')
    ->dailyAt('03:00')
    ->onFailure(fn () => logger()->warning('central:sync falló; se conserva el snapshot anterior'));
