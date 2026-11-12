<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = Carbon::now();

        $existing = DB::table('rol')->where('name', 'DIRECTOR GENERAL')->first();
        if (!$existing) {
            $rolId = DB::table('rol')->insertGetId([
                'id' => 9,
                'name' => 'DIRECTOR GENERAL',
                'key' => 'rol_general_director',
                'level' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        } else {
            $rolId = $existing->id;
        }

        $options = DB::table('rol_option')
            ->where('rol_id', 4)
            ->pluck('option_id')
            ->toArray();

        $extraOptions = [10, 11, 12, 14, 15, 31];
        $allOptions = array_unique(array_merge($options, $extraOptions));

        foreach ($allOptions as $optionId) {
            $exists = DB::table('rol_option')
                ->where('rol_id', $rolId)
                ->where('option_id', $optionId)
                ->exists();

            if (!$exists) {
                DB::table('rol_option')->insert([
                    'rol_id' => $rolId,
                    'option_id' => $optionId,
                ]);
            }
        }
    }

    public function down(): void
    {
        $rol = DB::table('rol')->where('name', 'DIRECTOR GENERAL')->first();
        if ($rol) {
            DB::table('rol_option')->where('rol_id', $rol->id)->delete();
            DB::table('rol')->where('id', $rol->id)->delete();
        }
    }
};
