<?php

declare(strict_types=1);

namespace Modules\Shared\Console;

use Modules\Shared\Services\DataCorrection;

/**
 * Vacía un periodo: sus aulas, matrículas, capacitaciones y movimientos de
 * tesorería, con todo lo que cuelga de ellos (notas, asistencias...). Conserva
 * el periodo, las personas, los catálogos y los demás periodos. Ver DataCorrection.
 */
class InstitutionPurgePeriodCommand extends DataCorrectionCommand
{
    protected $signature = 'institution:purge-period
                            {period : Nombre del periodo (ej. 2026-2) o su id}
                            {--force : Borra de verdad; sin él solo muestra qué se borraría}
                            {--json : Salida en JSON, para el orquestador}';

    protected $description = 'Vacía un periodo académico: vista previa, o borrado con --force';

    protected function correct(): array
    {
        return DataCorrection::purgePeriod((string) $this->argument('period'), (bool) $this->option('force'));
    }
}
