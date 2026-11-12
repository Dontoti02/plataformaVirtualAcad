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

    Schema::table('training_content', function (Blueprint $table) {
      $table->boolean('is_group_task')->after('has_evaluation_form')->default(false);
    });

    Schema::create('training_task_group', function (Blueprint $table) {
      $table->id();
      $table->foreignId('training_id')
        ->references('id')
        ->on('training')
        ->onUpdate('cascade')
        ->onDelete('cascade');
      $table->foreignId('training_content_id')
        ->references('id')
        ->on('training_content')
        ->onUpdate('cascade')
        ->onDelete('cascade');
      $table->foreignId('person_task_register_id')
        ->references('id')
        ->nullable()
        ->on('person')
        ->onUpdate('cascade')
        ->onDelete('cascade');
      $table->string('name');
      $table->decimal('score', 8, 2)->nullable();
      $table->integer('num_participants')->default(0);
      $table->boolean('task_send')->default(false);
      $table->dateTime('created_at')->nullable();
      $table->dateTime('updated_at')->nullable();
      $table->dateTime('deleted_at')->nullable();
    });

    Schema::create('training_task_group_participant', function (Blueprint $table) {

      $table->id();

      $table->foreignId('training_task_group_id')
        ->references('id')
        ->on('training_task_group')
        ->onUpdate('cascade')
        ->onDelete('cascade');

      $table->foreignId('training_participant_id')
        ->references('id')
        ->on('training_participant')
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
    Schema::dropIfExists('training_task_group_participant');
    Schema::dropIfExists('training_task_group');

    Schema::table('training_content', function (Blueprint $table) {
      $table->dropColumn('is_group_task');
    });
  }
};
