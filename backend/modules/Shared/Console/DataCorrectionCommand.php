<?php

declare(strict_types=1);

namespace Modules\Shared\Console;

use Illuminate\Console\Command;
use Modules\Tenant\Middleware\InitializeTenancySingle;
use RuntimeException;

/**
 * Lo común a institution:undo-import e institution:purge-period: solo en modo
 * single, sobre la BD de la institución, y con la salida legible o en JSON.
 */
abstract class DataCorrectionCommand extends Command
{
    abstract protected function correct(): array;

    public function handle(): int
    {
        if (config('tenancy.deploy_mode') !== 'single') {
            return $this->failWith('Solo aplica en DEPLOY_MODE=single.');
        }

        tenancy()->initialize(InitializeTenancySingle::tenant());

        try {
            $result = $this->correct();
        } catch (RuntimeException $e) {
            return $this->failWith($e->getMessage());
        }

        if ($this->option('json')) {
            $this->line(json_encode($result, JSON_UNESCAPED_UNICODE));
        } else {
            $this->render($result);
        }

        // Con --force, un bloqueo es un fallo: no se borró lo que se pidió.
        return $this->option('force') && ! $result['executed'] ? self::FAILURE : self::SUCCESS;
    }

    private function render(array $result): void
    {
        $this->table(
            ['Qué', 'Tabla', 'Se borran', 'De lo pedido'],
            array_map(fn ($row) => [$row['label'], $row['table'], $row['deleted'], $row['own'] ?? 'en cascada'], $result['tables']),
        );
        $this->line("Total: {$result['total']} filas.");

        foreach ($result['notes'] as $note) {
            $this->line("· {$note}");
        }
        foreach ($result['warnings'] as $warning) {
            $this->warn("AVISO: {$warning}");
        }
        foreach ($result['blocked'] as $reason) {
            $this->error("BLOQUEADO: {$reason}");
        }

        $this->newLine();

        if ($result['executed']) {
            $this->info('Hecho: los datos se borraron.');
        } elseif ($result['blocked'] === []) {
            $this->info('Vista previa: no se ha borrado nada. Repite con --force para hacerlo.');
        } else {
            $this->warn('No se ha borrado nada.');
        }
    }

    private function failWith(string $message): int
    {
        if ($this->option('json')) {
            $this->line(json_encode(['ok' => false, 'error' => $message], JSON_UNESCAPED_UNICODE));
        } else {
            $this->error($message);
        }

        return self::FAILURE;
    }
}
