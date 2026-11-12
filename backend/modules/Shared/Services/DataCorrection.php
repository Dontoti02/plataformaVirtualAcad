<?php

declare(strict_types=1);

namespace Modules\Shared\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

/**
 * Corrección de datos cargados por error: deshacer una importación o vaciar un
 * periodo. La usan institution:undo-import e institution:purge-period, que
 * lanza el orquestador desde «Corregir datos».
 *
 * Casi todas las claves foráneas del tenant son ON DELETE CASCADE: borrar un
 * docente borra sus aulas, y con ellas sus notas. Por eso nada se borra a
 * ciegas: todo va dentro de una transacción, se cuentan TODAS las tablas antes
 * y después, y solo se confirma si lo que se lleva por delante es lo esperado.
 * Sin $force siempre se deshace: es la vista previa.
 */
final class DataCorrection
{
    /**
     * Lo que inserta cada importación, todo con created_at = import_detail.date
     * (ImportRepository::executeJob usa el mismo instante). De hijas a padres:
     * así, lo que borren las cascadas son filas AJENAS a la importación, y se ven.
     */
    public const IMPORT_TABLES = [
        'staff'          => ['staff', 'user', 'person', 'period'],
        'study_programs' => ['program_plan', 'study_plan', 'study_program', 'productive_family'],
        'registrations'  => ['student', 'user', 'person', 'cycle'],
        'evaluations'    => ['participant', 'classroom', 'course', 'section', 'shift'],
    ];

    /**
     * Tablas de IMPORT_TABLES sin created_at: sus filas son de la importación si
     * cuelgan de un padre que ella creó (columna => tabla padre). Sin esto la
     * vista previa fallaba con «Unknown column 'created_at'» en program_plan.
     */
    private const LINKED_BY_PARENT = [
        'program_plan' => ['study_program_id' => 'study_program', 'study_plan_id' => 'study_plan'],
    ];

    /** Cada importación, como la llama la pantalla de Importaciones. */
    public const IMPORT_LABELS = [
        'staff'          => 'Personal',
        'study_programs' => 'Programas de estudio',
        'registrations'  => 'Estudiantes matriculados',
        'evaluations'    => 'Notas',
    ];

    /** Nombres para personas: la vista previa la lee quien decide si borrar. */
    public const TABLE_LABELS = [
        'period' => 'Periodos', 'person' => 'Personas', 'user' => 'Usuarios', 'rol_user' => 'Roles de usuario',
        'teacher' => 'Docentes', 'staff' => 'Personal', 'student' => 'Estudiantes',
        'enrollment' => 'Matrículas', 'cycle' => 'Ciclos', 'course' => 'Unidades didácticas',
        'program_plan' => 'Programas y planes', 'study_program' => 'Programas de estudio',
        'study_plan' => 'Planes de estudio', 'productive_family' => 'Familias productivas',
        'shift' => 'Turnos', 'section' => 'Secciones', 'classroom' => 'Aulas', 'participant' => 'Notas de los participantes',
        'training' => 'Capacitaciones', 'treasury_movement' => 'Movimientos de tesorería',
        'evaluation_group' => 'Grupos de evaluación', 'content_group' => 'Grupos de contenido', 'content' => 'Contenidos',
        'assistance' => 'Asistencias', 'publication' => 'Publicaciones', 'comment' => 'Comentarios', 'answer' => 'Respuestas',
        'schedule' => 'Horarios', 'file' => 'Archivos', 'link' => 'Enlaces',
    ];

    /** Un periodo abierto: aquí no hay columna is_current, se marca con el estado. */
    private const PERIOD_OPEN = 'SIN TERMINO';

    /** Lo que cuelga directamente de un periodo: lo que borra «vaciar un periodo». */
    public const PERIOD_TABLES = ['classroom', 'enrollment', 'training', 'treasury_movement', 'student', 'staff'];

    /**
     * Datos de los que depende el resto. Si el borrado se lleva filas de estas
     * tablas que no le corresponden, se bloquea: serían datos buenos.
     */
    private const CORE = [
        'period', 'person', 'user', 'teacher', 'staff', 'student', 'enrollment',
        'cycle', 'course', 'study_program', 'study_plan', 'program_plan',
        'productive_family', 'shift', 'section', 'classroom', 'participant',
        'training', 'treasury_movement',
    ];

    public static function undoImport(int $detailId, bool $force): array
    {
        $detail = DB::table('import_detail')
            ->join('import', 'import.id', '=', 'import_detail.import_id')
            ->where('import_detail.id', $detailId)
            ->select('import_detail.*', 'import.key', 'import.title')
            ->first();

        if (! $detail) {
            throw new RuntimeException("No existe la importación #{$detailId}.");
        }

        $tables = self::IMPORT_TABLES[$detail->key]
            ?? throw new RuntimeException("Tipo de importación desconocido: {$detail->key}.");

        $date = $detail->date;
        $blocked = self::importRunning() ? ['Hay una importación en curso: espera a que termine.'] : [];
        $warnings = [];
        $notes = [];

        if ($detail->status === 'reverted') {
            $blocked[] = 'Esta importación ya se deshizo.';
        }

        $current = DB::table('period')->where('created_at', $date)->where('status', self::PERIOD_OPEN)->pluck('name');

        if (in_array('period', $tables, true) && $current->isNotEmpty()) {
            $warnings[] = 'Se borra el periodo '.$current->implode(', ').', marcado como «Actual»: después marca otro como actual.';
        }

        $periods = [];

        if ($periods !== []) {
            $notes[] = 'Se recalculan los estados de aprobación de las matrículas de '
                .DB::table('period')->whereIn('id', $periods)->pluck('name')->implode(', ').'.';
        }

        return self::run(
            action: 'undo_import',
            target: [
                'id' => (int) $detail->id,
                'key' => $detail->key,
                'type' => self::IMPORT_LABELS[$detail->key] ?? $detail->key,
                'date' => (string) $date,
                'status' => $detail->status,
            ],
            what: 'esta importación',
            core: self::CORE,
            own: fn () => array_combine($tables, array_map(
                fn (string $table) => self::importRows($table, $date)->count(),
                $tables,
            )),
            delete: function () use ($tables, $date) {
                // De hijas a padres: las tablas enlazadas se borran mientras sus
                // padres aún existen y se pueden reconocer.
                foreach ($tables as $table) {
                    self::importRows($table, $date)->delete();
                }
            },
            finish: function (int $total) use ($detail, $periods) {
                self::markReverted($detail, $total);

                // Esta línea no recalcula estados tras importar.
            },
            force: $force,
            blocked: $blocked,
            warnings: $warnings,
            notes: $notes,
        );
    }

    public static function purgePeriod(string $period, bool $force): array
    {
        $row = DB::table('period')
            ->where('name', $period)
            ->when(ctype_digit($period), fn ($q) => $q->orWhere('id', (int) $period))
            ->first();

        if (! $row) {
            throw new RuntimeException("No existe el periodo «{$period}».");
        }

        $blocked = self::importRunning() ? ['Hay una importación en curso: espera a que termine.'] : [];
        $warnings = [];

        $treasury = DB::table('treasury_movement')->where('period_id', $row->id)->count();

        if ($treasury > 0) {
            $warnings[] = "Se borran {$treasury} movimientos de tesorería (pagos y cobros) del periodo.";
        }

        return self::run(
            action: 'purge_period',
            target: ['id' => (int) $row->id, 'name' => $row->name, 'is_current' => $row->status === self::PERIOD_OPEN],
            what: 'este periodo',
            // Los participantes de sus aulas se van con ellas: es lo que se pide.
            core: array_values(array_diff(self::CORE, ['participant'])),
            own: fn () => array_combine(self::PERIOD_TABLES, array_map(
                fn (string $table) => DB::table($table)->where('period_id', $row->id)->count(),
                self::PERIOD_TABLES,
            )),
            delete: function () use ($row) {
                foreach (self::PERIOD_TABLES as $table) {
                    DB::table($table)->where('period_id', $row->id)->delete();
                }
            },
            finish: null,
            force: $force,
            blocked: $blocked,
            warnings: $warnings,
            notes: ['El periodo se conserva, vacío: se le pueden volver a importar sus datos.'],
        );
    }

    /** Importaciones y periodos, para elegir en el panel qué corregir. */
    public static function report(): array
    {
        $imports = DB::table('import_detail')
            ->join('import', 'import.id', '=', 'import_detail.import_id')
            ->orderByDesc('import_detail.date')
            ->get(['import_detail.id', 'import.key', 'import_detail.status', 'import_detail.date', 'import_detail.summary'])
            ->map(fn ($row) => [
                'id' => (int) $row->id,
                'key' => $row->key,
                // No import.title: es uno por tipo y lo pisa el último archivo subido.
                'type' => self::IMPORT_LABELS[$row->key] ?? $row->key,
                'status' => $row->status,
                'date' => (string) $row->date,
                'summary' => json_decode((string) $row->summary, true) ?: [],
            ]);

        $periods = DB::table('period')->orderByDesc('id')->get(['id', 'name', 'status', 'created_at'])
            ->map(fn ($row) => [
                'id' => (int) $row->id,
                'name' => $row->name,
                'is_current' => $row->status === self::PERIOD_OPEN,
                'classrooms' => DB::table('classroom')->where('period_id', $row->id)->count(),
                'enrollments' => DB::table('enrollment')->where('period_id', $row->id)->count(),
                'participants' => DB::table('participant')
                    ->join('classroom', 'classroom.id', '=', 'participant.classroom_id')
                    ->where('classroom.period_id', $row->id)->count(),
                'trainings' => DB::table('training')->where('period_id', $row->id)->count(),
                'treasury' => DB::table('treasury_movement')->where('period_id', $row->id)->count(),
            ]);

        return ['imports' => $imports->all(), 'periods' => $periods->all()];
    }

    /**
     * @param  callable(): array<string, int>  $own  tabla => filas que le corresponden
     */
    private static function run(
        string $action,
        array $target,
        string $what,
        array $core,
        callable $own,
        callable $delete,
        ?callable $finish,
        bool $force,
        array $blocked,
        array $warnings,
        array $notes,
    ): array {
        $connection = DB::connection();
        $connection->beginTransaction();

        try {
            // Dentro de la transacción: la primera lectura fija la foto, y lo que
            // escriban otros mientras tanto no se confunde con lo que borramos.
            $before = self::counts();
            $expected = $own();
            $delete();
            $after = self::counts();

            $tables = [];

            foreach ($before as $table => $count) {
                $deleted = $count - ($after[$table] ?? 0);
                $mine = $expected[$table] ?? null;

                if ($deleted === 0 && $mine === null) {
                    continue;
                }

                $label = self::TABLE_LABELS[$table] ?? $table;
                $tables[] = ['table' => $table, 'label' => $label, 'deleted' => $deleted, 'own' => $mine];

                if (in_array($table, $core, true) && $deleted > ($mine ?? 0)) {
                    $blocked[] = sprintf(
                        'Se borrarían %s %s que no son de %s: dependen de ello datos cargados después o a mano.',
                        number_format($deleted - ($mine ?? 0), 0, ',', '.'),
                        mb_strtolower($label),
                        $what,
                    );
                }
            }

            $total = array_sum(array_column($tables, 'deleted'));

            if ($total === 0) {
                $blocked[] = 'No hay nada que borrar.';
            }

            $executed = $force && $blocked === [];

            if ($executed) {
                if ($finish) {
                    $finish($total);
                }
                $connection->commit();
            } else {
                $connection->rollBack();
            }
        } catch (Throwable $e) {
            $connection->rollBack();

            throw $e;
        }

        usort($tables, fn ($a, $b) => $b['deleted'] <=> $a['deleted']);

        return [
            'ok' => $blocked === [],
            'executed' => $executed,
            'action' => $action,
            'target' => $target,
            'total' => $total,
            'tables' => $tables,
            'blocked' => array_values(array_unique($blocked)),
            'warnings' => $warnings,
            'notes' => $notes,
        ];
    }

    /** @return array<string, int> */
    private static function counts(): array
    {
        $tables = DB::select(
            'SELECT table_name AS t FROM information_schema.tables WHERE table_schema = ? AND table_type = ? ORDER BY table_name',
            [DB::connection()->getDatabaseName(), 'BASE TABLE'],
        );

        $counts = [];

        foreach ($tables as $row) {
            $counts[$row->t] = DB::table($row->t)->count();
        }

        return $counts;
    }

    /** Las filas de $table que trajo la importación del instante $date. */
    private static function importRows(string $table, string $date): \Illuminate\Database\Query\Builder
    {
        if (! isset(self::LINKED_BY_PARENT[$table])) {
            return DB::table($table)->where('created_at', $date);
        }

        return DB::table($table)->where(function ($query) use ($table, $date) {
            foreach (self::LINKED_BY_PARENT[$table] as $column => $parent) {
                $query->orWhereIn($column, DB::table($parent)->where('created_at', $date)->select('id'));
            }
        });
    }

    private static function importRunning(): bool
    {
        return DB::table('import_detail')->where('is_active', true)->exists();
    }

    /**
     * Queda en el historial como «Deshecha» (no se borra: se sabe qué pasó), y
     * la anterior de ese tipo que siga en pie vuelve a ser la vigente.
     */
    private static function markReverted(object $detail, int $total): void
    {
        $log = json_decode((string) $detail->log, true) ?: [];
        $log[] = now()->format('Y-m-d H:i:s')." | Importación deshecha desde el orquestador: {$total} registros borrados";

        DB::table('import_detail')->where('id', $detail->id)->update([
            'status' => 'reverted',
            'is_current' => false,
            'is_active' => false,
            'log' => json_encode($log, JSON_UNESCAPED_UNICODE),
        ]);

        if ($detail->is_current) {
            $previous = DB::table('import_detail')
                ->where('import_id', $detail->import_id)
                ->where('status', '!=', 'reverted')
                ->orderByDesc('date')
                ->value('id');

            if ($previous) {
                DB::table('import_detail')->where('id', $previous)->update(['is_current' => true]);
            }
        }
    }
}
