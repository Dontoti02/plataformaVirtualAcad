<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = Carbon::now();

        // 1. JEFE DE UNIDAD ACADÉMICA (id: 10)
        $jefe = DB::table('rol')->where('name', 'JEFE DE UNIDAD ACADÉMICA')->first();
        if (!$jefe) {
            $jefeId = DB::table('rol')->insertGetId([
                'id' => 10,
                'name' => 'JEFE DE UNIDAD ACADÉMICA',
                'key' => 'rol_academic_unit_head',
                'level' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        } else {
            $jefeId = $jefe->id;
        }

        // 2. COORDINADOR ACADÉMICO (id: 11)
        $coord = DB::table('rol')->where('name', 'COORDINADOR ACADÉMICO')->first();
        if (!$coord) {
            $coordId = DB::table('rol')->insertGetId([
                'id' => 11,
                'name' => 'COORDINADOR ACADÉMICO',
                'key' => 'rol_academic_coordinator',
                'level' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        } else {
            $coordId = $coord->id;
        }

        // Options for JEFE DE UNIDAD ACADÉMICA (All administrative and academic options)
        $jefeOptions = [1, 3, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 26, 27, 28, 30, 31];
        foreach ($jefeOptions as $optionId) {
            $exists = DB::table('rol_option')
                ->where('rol_id', $jefeId)
                ->where('option_id', $optionId)
                ->exists();

            if (!$exists) {
                DB::table('rol_option')->insert([
                    'rol_id' => $jefeId,
                    'option_id' => $optionId,
                ]);
            }
        }

        // Options for COORDINADOR ACADÉMICO (Academic management options)
        $coordOptions = [1, 3, 6, 7, 8, 10, 11, 12, 14, 15, 31];
        foreach ($coordOptions as $optionId) {
            $exists = DB::table('rol_option')
                ->where('rol_id', $coordId)
                ->where('option_id', $optionId)
                ->exists();

            if (!$exists) {
                DB::table('rol_option')->insert([
                    'rol_id' => $coordId,
                    'option_id' => $optionId,
                ]);
            }
        }
    }

    public function down(): void
    {
        $jefe = DB::table('rol')->where('name', 'JEFE DE UNIDAD ACADÉMICA')->first();
        if ($jefe) {
            DB::table('rol_option')->where('rol_id', $jefe->id)->delete();
            DB::table('rol')->where('id', $jefe->id)->delete();
        }

        $coord = DB::table('rol')->where('name', 'COORDINADOR ACADÉMICO')->first();
        if ($coord) {
            DB::table('rol_option')->where('rol_id', $coord->id)->delete();
            DB::table('rol')->where('id', $coord->id)->delete();
        }
    }
};
