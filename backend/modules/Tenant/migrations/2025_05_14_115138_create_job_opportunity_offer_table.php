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
    Schema::create('job_opportunity_offer', function (Blueprint $table) {
      $table->id();
      $table->string('title',255);
      $table->string('slug',255)->unique();
      $table->text('description');
      $table->text('requirements');
      $table->dateTime('publication_date');
      $table->dateTime('deadline')->nullable();
      $table->text('benefits')->nullable();
      $table->decimal('salary',10,2);
      $table->string('salary_currency');
      $table->string('attachments')->nullable();
      $table->string('address');
      $table->string('department');
      $table->string('province');
      $table->string('country')->nullable();
      $table->foreignId('company_id')
        ->references('id')
        ->on('job_opportunity_company')
        ->onUpdate('cascade')
        ->onDelete('cascade');

      $table->foreignId('location_id')
        ->references('id')
        ->on('job_opportunity_location')
        ->onUpdate('cascade')
        ->onDelete('cascade');

      $table->foreignId('state_id')
        ->references('id')
        ->on('job_opportunity_offer_state')
        ->onUpdate('cascade')
        ->onDelete('cascade');

      $table->foreignId('category_id')
        ->references('id')
        ->on('job_opportunity_offer_category')
        ->onUpdate('cascade')
        ->onDelete('cascade');

      $table->foreignId('work_schedule_id')
        ->references('id')
        ->on('job_opportunity_work_schedules')
        ->onUpdate('cascade')
        ->onDelete('cascade');

      $table->foreignId('contract_type_id')
        ->references('id')
        ->on('job_opportunity_contract_types')
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
    Schema::dropIfExists('job_opportunity_offer');
  }
};
