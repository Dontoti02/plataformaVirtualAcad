<?php

declare(strict_types=1);

namespace Modules\Shared\Console;

use Modules\Shared\Services\DataCorrection;

/**
 * Deshace una importación: borra exactamente lo que creó (sus filas llevan la
 * fecha de la importación en created_at) y lo que cuelga de ello. Si se llevara
 * datos que no creó, no borra nada. Ver DataCorrection.
 */
class InstitutionUndoImportCommand extends DataCorrectionCommand
{
    protected $signature = 'institution:undo-import
                            {import : Número de la importación (el # del historial)}
                            {--force : Borra de verdad; sin él solo muestra qué se borraría}
                            {--json : Salida en JSON, para el orquestador}';

    protected $description = 'Deshace una importación: vista previa, o borrado con --force';

    protected function correct(): array
    {
        return DataCorrection::undoImport((int) $this->argument('import'), (bool) $this->option('force'));
    }
}
