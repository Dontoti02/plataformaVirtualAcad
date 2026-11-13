<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Modules\Tenant\Modules\User\Enums\RolTenant;

return new class extends Migration
{
    private const NAME_URL = 'FailedStudentsReport';

    private const ROLES = [
        RolTenant::COORDINADOR_ACADEMICO, // 11
        RolTenant::JEFE_UNIDAD_ACADEMICA, // 10
        RolTenant::DIRECTOR_GENERAL,      // 9
        RolTenant::ADMINISTRADOR,         // 4
        RolTenant::SECRETARY,             // 1
    ];

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
                'name' => 'Estudiantes no aprobados',
                'name_url' => self::NAME_URL,
                'icon' => 'tabler-user-x',
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
