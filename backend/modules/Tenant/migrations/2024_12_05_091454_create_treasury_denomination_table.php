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
        Schema::create('treasury_denomination', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('description')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->dateTime('deleted_at')->nullable();
        });


      // TUPA DATA
      
      $date = Carbon::now();

      DB::table('treasury_denomination')->insert([
          'name' => 'MATRICULA',
          'description' => 'Pago por concepto de matrícula',
          'created_at' => $date,
          'updated_at' => $date,
      ]);
      
      DB::table('treasury_denomination')->insert([
          'name' => 'PENSIONES DE ENSEÑANZA',
          'description' => 'Pago por concepto de pensión de enseñanza',
          'created_at' => $date,
          'updated_at' => $date,
      ]);

      DB::table('treasury_denomination')->insert([
          'name' => 'TRASLADOS, REPITENCIA Y REINCORPORACION',
          'description' => 'Pago por concepto de traslado, repitencia y reincorporación',
          'created_at' => $date,
          'updated_at' => $date,
      ]);

      DB::table('treasury_denomination')->insert([
          'name' => 'SUBSANACION DE UNIDADES DIDACTICAS',
          'description' => 'Pago por concepto de subsanación de unidades didácticas',
          'created_at' => $date,
          'updated_at' => $date,
      ]);

      DB::table('treasury_denomination')->insert([
          'name' => 'CONSTANCIAS Y CERTIFICADOS',
          'description' => 'Pago por concepto de constancias y certificados',
          'created_at' => $date,
          'updated_at' => $date,
      ]);

      DB::table('treasury_denomination')->insert([
          'name' => 'GRADOS Y TITULOS',
          'description' => 'Pago por concepto de grados y títulos',
          'created_at' => $date,
          'updated_at' => $date,
      ]);

      DB::table('treasury_denomination')->insert([
          'name' => 'EFSRT',
          'description' => 'Pago por concepto de EFSRT',
          'created_at' => $date,
          'updated_at' => $date,
      ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('treasury_denomination');
    }
};
