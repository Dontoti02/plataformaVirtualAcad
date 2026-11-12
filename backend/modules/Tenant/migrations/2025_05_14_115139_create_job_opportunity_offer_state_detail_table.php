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
    Schema::create('job_opportunity_offer_state_detail', function (Blueprint $table) {
      $table->id();
      $table->foreignId('offer_id')
        ->references('id')
        ->on('job_opportunity_offer')
        ->onUpdate('cascade')
        ->onDelete('cascade');

      $table->foreignId('state_id')
        ->references('id')
        ->on('job_opportunity_offer_state')
        ->onUpdate('cascade')
        ->onDelete('cascade');
      $table->dateTime('created_at')->nullable();
      $table->dateTime('updated_at')->nullable();
      $table->dateTime('deleted_at')->nullable();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('job_opportunity_offer_state_detail');
  }
};
