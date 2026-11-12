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
    Schema::create('job_opportunity_offer_state', function (Blueprint $table) {
      $table->id();
      $table->string('name');
      $table->string('key');
      $table->string('description')->nullable();
      $table->dateTime('created_at')->nullable();
      $table->dateTime('updated_at')->nullable();
      $table->dateTime('deleted_at')->nullable();
    });

    DB::table('job_opportunity_offer_state')->insert([
      ['name' => 'Borrador', 'key' => 'draft', 'created_at' => now(), 'updated_at' => now()],
      ['name' => 'Vigente', 'key' => 'active', 'created_at' => now(), 'updated_at' => now()],
      ['name' => 'Finalizada', 'key' => 'finished', 'created_at' => now(), 'updated_at' => now()],
      ['name' => 'Suspendida', 'key' => 'suspended', 'created_at' => now(), 'updated_at' => now()],
      ['name' => 'Cancelada', 'key' => 'canceled', 'created_at' => now(), 'updated_at' => now()],
    ]);
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('job_opportunity_offer_state');
  }
};
