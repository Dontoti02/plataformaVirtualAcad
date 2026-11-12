<?php

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
    Schema::create('job_opportunity_work_schedules', function (Blueprint $table) {
      $table->id();
      $table->string('name');
      $table->dateTime('created_at')->nullable();
      $table->dateTime('updated_at')->nullable();
      $table->dateTime('deleted_at')->nullable();
    });

    DB::table('job_opportunity_work_schedules')->insert([
      ['name' => 'Jornada Completa', 'created_at' => now(), 'updated_at' => now()],
      ['name' => 'Becas/Prácticas', 'created_at' => now(), 'updated_at' => now()],
      ['name' => 'Jornada Parcial', 'created_at' => now(), 'updated_at' => now()],
      ['name' => 'Por Horas', 'created_at' => now(), 'updated_at' => now()],
    ]);
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('job_opportunity_work_schedules');
  }
};
