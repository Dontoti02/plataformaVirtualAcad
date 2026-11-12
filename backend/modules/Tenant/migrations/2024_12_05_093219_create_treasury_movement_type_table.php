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
        Schema::create('treasury_movement_type', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique();
            $table->string('name');
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->dateTime('deleted_at')->nullable();
        });

        $date = Carbon::now();

        DB::table('treasury_movement_type')->insert([
          'id' => 1,
          'code' => "INGRESO",
          'name' => 'Ingreso',
          'created_at' => $date,
          'updated_at' => $date,
        ]);

        DB::table('treasury_movement_type')->insert([
          'id' => 2,
          'code' => "EGRESO",
          'name' => "Egreso",
          'created_at' => $date,
          'updated_at' => $date,
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('treasury_movement_type');
    }
};
