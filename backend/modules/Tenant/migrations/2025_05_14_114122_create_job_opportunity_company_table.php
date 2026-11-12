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
    Schema::create('job_opportunity_company', function (Blueprint $table) {
      $table->id();
      $table->string('name');
      $table->string('ruc')->unique();
      $table->string('email');
      $table->string('phone');
      $table->string('mailbox');
      $table->boolean('is_verified')->default(false);
      $table->text('description')->nullable();
      $table->string('website')->nullable();
      $table->string('address')->nullable();
      $table->string('logo')->nullable();
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
    Schema::dropIfExists('job_opportunity_company');
  }
};
