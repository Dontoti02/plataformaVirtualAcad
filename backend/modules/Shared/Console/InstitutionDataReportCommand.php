<?php

declare(strict_types=1);

namespace Modules\Shared\Console;

use Illuminate\Console\Command;
use Modules\Shared\Services\DataCorrection;
use Modules\Tenant\Middleware\InitializeTenancySingle;

/**
 * Importaciones y periodos de la institución, con lo que tiene cada periodo.
 * Es lo que muestra el orquestador en «Corregir datos» para elegir qué deshacer.
 */
class InstitutionDataReportCommand extends Command
{
    protected $signature = 'institution:data-report {--json : Salida en JSON, para el orquestador}';

    protected $description = 'Lista las importaciones y los periodos de la institución';

    public function handle(): int
    {
        if (config('tenancy.deploy_mode') !== 'single') {
            $this->error('Solo aplica en DEPLOY_MODE=single.');

            return self::FAILURE;
        }

        tenancy()->initialize(InitializeTenancySingle::tenant());

        $report = DataCorrection::report();

        if ($this->option('json')) {
            $this->line(json_encode($report, JSON_UNESCAPED_UNICODE));

            return self::SUCCESS;
        }

        $this->table(['#', 'Importación', 'Estado', 'Fecha'], array_map(
            fn ($i) => [$i['id'], $i['type'], $i['status'], $i['date']],
            $report['imports'],
        ));
        $this->table(['Periodo', 'Aulas', 'Matrículas', 'Participantes', 'Capacitaciones', 'Tesorería'], array_map(
            fn ($p) => [$p['name'].($p['is_current'] ? ' (actual)' : ''), $p['classrooms'], $p['enrollments'], $p['participants'], $p['trainings'], $p['treasury']],
            $report['periods'],
        ));

        return self::SUCCESS;
    }
}
