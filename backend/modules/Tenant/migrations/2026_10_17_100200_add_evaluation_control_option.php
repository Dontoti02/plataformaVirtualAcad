<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * «Reportes de evaluación» en Procesos Académicos: la tabla de notas por
 * capacidad e indicador de cualquier clase. Mismos roles que en la línea
 * actual (secretaría y docente) más administración, que aquí tiene el resto
 * de Procesos Académicos. Repetible.
 */
return new class extends Migration
{
    private const NAME_URL = 'EvaluationControl';

    private const ROLES = [1, 2, 4]; // secretaría, docente, administración

    public function up(): void
    {
        $menuId = DB::table('menu')->where('name', 'Procesos Académicos')->value('id');

        if (! $menuId) {
            return;
        }

        $optionId = DB::table('option')->where('name_url', self::NAME_URL)->value('id');

        if (! $optionId) {
            $optionId = DB::table('option')->insertGetId([
                'menu_id' => $menuId,
                'name' => 'Reportes de evaluación',
                'name_url' => self::NAME_URL,
                'is_visible' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        foreach (self::ROLES as $rolId) {
            $exists = DB::table('rol_option')
                ->where('rol_id', $rolId)
                ->where('option_id', $optionId)
                ->exists();

            if (! $exists && DB::table('rol')->where('id', $rolId)->exists()) {
                DB::table('rol_option')->insert(['rol_id' => $rolId, 'option_id' => $optionId]);
            }
        }
    }

    public function down(): void
    {
        $optionId = DB::table('option')->where('name_url', self::NAME_URL)->value('id');

        if ($optionId) {
            DB::table('rol_option')->where('option_id', $optionId)->delete();
            DB::table('option')->where('id', $optionId)->delete();
        }
    }
};
