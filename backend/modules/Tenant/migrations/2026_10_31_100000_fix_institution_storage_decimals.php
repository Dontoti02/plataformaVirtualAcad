<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * El espacio usado se lleva en MB con decimales, pero las columnas locales eran
 * enteras: un PDF de 0,08 MB sumaba 0 al subirlo y al borrarlo el contador
 * quedaba negativo (error 1264). Se pasan a decimal y se recalcula lo usado con
 * los archivos que existen de verdad.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('institution_storage')) {
            return;
        }

        Schema::table('institution_storage', function (Blueprint $table) {
            $table->decimal('used_space_mb', 18, 8)->default(0)->change();
            $table->decimal('free_space_mb', 18, 8)->default(0)->change();
        });

        $used = 0.0;

        foreach (DB::table('file')->whereNull('deleted_at')->select('metadata')->cursor() as $file) {
            $used += (float) (json_decode((string) $file->metadata)->size ?? 0);
        }

        foreach (DB::table('institution_storage')->get() as $storage) {
            DB::table('institution_storage')->where('id', $storage->id)->update([
                'used_space_mb' => $used,
                'free_space_mb' => max(0, $storage->limit_space_mb - $used),
            ]);
        }
    }

    public function down(): void
    {
        // Volver a enteros perdería los decimales: no se deshace.
    }
};
