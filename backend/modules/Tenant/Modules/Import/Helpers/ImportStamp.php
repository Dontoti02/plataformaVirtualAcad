<?php

declare(strict_types=1);

namespace Modules\Tenant\Modules\Import\Helpers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;

/**
 * Marca todo lo que crea una importación con SU instante.
 *
 * Sin esto no hay forma de saber qué trajo cada importación: los registros se
 * crean uno a uno y cada uno queda con la hora en que le tocó. Con la marca,
 * «Deshacer una importación» (institution:undo-import) borra exactamente sus
 * filas, comparando created_at con import_detail.date.
 *
 * Se hace con un oyente de Eloquent en vez de tocar los ~17 sitios donde la
 * importación crea registros: así también vale para el código que venga.
 * `updateTimestamps()` respeta lo que se fije aquí, porque solo rellena las
 * marcas que no estén ya modificadas.
 */
final class ImportStamp
{
    private static ?string $at = null;

    private static bool $listening = false;

    /**
     * Ejecuta la importación marcando lo que cree con $at.
     *
     * @template T
     * @param  callable(): T  $import
     * @return T
     */
    public static function during(string $at, callable $import): mixed
    {
        self::listen();
        self::$at = $at;

        try {
            return $import();
        } finally {
            // El worker sigue vivo y atenderá otros trabajos: fuera la marca.
            self::$at = null;
        }
    }

    private static function listen(): void
    {
        if (self::$listening) {
            return;
        }

        self::$listening = true;

        Event::listen('eloquent.creating: *', function (string $event, array $models): void {
            if (self::$at === null) {
                return;
            }

            foreach ($models as $model) {
                if ($model instanceof Model && $model->usesTimestamps()) {
                    $model->setCreatedAt(self::$at);
                    $model->setUpdatedAt(self::$at);
                }
            }
        });
    }
}
