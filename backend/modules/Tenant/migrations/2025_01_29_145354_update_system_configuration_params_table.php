<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
      
      $date = Carbon::now();

      DB::table('system_configuration')->insert([
        'key' => 'igv_amount',
        'name' => 'Impuesto general a las ventas aplicado a los conceptos de pago',
        'type' => 'number',
        'value' => 0.18,
        'created_at' => $date,
        'updated_at' => $date,
      ]);

      Schema::table('period', function (Blueprint $table) {
          if (!Schema::hasColumn('period', 'start_date')) {
              $table->dateTime('start_date')->nullable();
          }
          if (!Schema::hasColumn('period', 'end_date')) {
              $table->dateTime('end_date')->nullable();
          }
      });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
