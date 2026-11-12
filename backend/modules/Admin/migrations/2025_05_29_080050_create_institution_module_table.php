<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  /**
   * Run the migrations.
   */
  public function up(): void
  {
    Schema::create('institution_module', function (Blueprint $table) {
      $table->id();
      $table->string('name', 255);
      $table->foreignId('institution_id')
        ->constrained('institution')
        ->onDelete('cascade');
      $table->string('module_key', 100);
      $table->boolean('is_active')->default(false);
      $table->date('start_date')->nullable();
      $table->date('end_date')->nullable();
      $table->json('settings')->nullable();
      $table->timestamps();
      $table->unique(['institution_id', 'module_key']);
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('institution_module');
  }
};
