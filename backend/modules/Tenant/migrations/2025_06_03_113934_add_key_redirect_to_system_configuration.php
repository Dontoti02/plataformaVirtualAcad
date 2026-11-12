<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
      $date = Carbon::now();
      DB::table('system_configuration')->insertGetId([
        'key' => 'redirect_links',
        'name' => 'Enlaces de redirección',
        'type' => 'array',
        'value' => json_encode([
          ["name" => "Admisión", "link" => ""]
      ]),
        'created_at' => $date,
        'updated_at' => $date,
     ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
      
    }
};
