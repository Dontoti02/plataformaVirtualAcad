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

        Schema::create('training_category', function (Blueprint $table) {
            $table->id();
            $table->string('name');
        });

        DB::table('training_category')->insert([
            'id' => 1,
            'name' => 'Categoría 1',
        ]);

        Schema::create('training_status', function (Blueprint $table) {
            $table->id();
            $table->string('name');
        });

        DB::table('training_status')->insert([
            ['id' => 1, 'name' => 'No iniciado'],
            ['id' => 2, 'name' => 'En curso'],
            ['id' => 3, 'name' => 'Finalizado'],
        ]);

        Schema::create('training', function (Blueprint $table) {
            $table->id();
            $table->foreignId('period_id')
                ->references('id')
                ->on('period')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->foreignId('training_category_id')
                ->references('id')
                ->on('training_category')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->foreignId('training_status_id')
                ->references('id')
                ->on('training_status')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->string('name');
            $table->string('image')->nullable();
            $table->integer('num_max_absences');
            $table->dateTime('start_date');
            $table->dateTime('end_date');
            $table->integer('min_participants');
            $table->integer('max_participants');
            $table->text('short_description');
            $table->longText('long_description');
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->dateTime('deleted_at')->nullable();
        });

        Schema::create('training_teacher', function (Blueprint $table) {
            $table->id();
            $table->foreignId('person_id')
                ->references('id')
                ->on('person')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->foreignId('training_id')
                ->references('id')
                ->on('training')
                ->onUpdate('cascade')
                ->onDelete('cascade');
        });

        Schema::create('training_participant', function (Blueprint $table) {
            $table->id();
            $table->foreignId('person_id')
                ->references('id')
                ->on('person')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->foreignId('training_id')
                ->references('id')
                ->on('training')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->decimal('score', 4, 2)->default(0.00);
            $table->boolean('is_favorite')->default(false);
            $table->boolean('is_active')->nullable()->default(true);
            $table->text('justification')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->dateTime('deleted_at')->nullable();
        });

        Schema::create('training_evaluation_group', function (Blueprint $table) {
            $table->id();
            $table->foreignId('training_id')
                ->references('id')
                ->on('training')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->string('title');
            $table->decimal('weight', 3, 2);
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->dateTime('deleted_at')->nullable();
        });


        Schema::create('training_content_group', function (Blueprint $table) {
            $table->id();
            $table->foreignId('training_id')
                ->references('id')
                ->on('training')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->string('title');
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->dateTime('deleted_at')->nullable();
        });


        Schema::create('training_average', function (Blueprint $table) {
            $table->id();
            $table->foreignId('person_id')
                ->references('id')
                ->on('person')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->foreignId('training_evaluation_group_id')
                ->references('id')
                ->on('training_evaluation_group')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->decimal('score', 4, 2)->default(0.00);
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->dateTime('deleted_at')->nullable();
        });

        Schema::create('training_average_detail', function (Blueprint $table) {
            $table->id();
            $table->foreignId('person_id')
                ->references('id')
                ->on('person')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->foreignId('training_content_group_id')
                ->references('id')
                ->on('training_content_group')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->foreignId('training_evaluation_group_id')
                ->references('id')
                ->on('training_evaluation_group')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->decimal('score', 4, 2)->default(0.00);
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->dateTime('deleted_at')->nullable();
        });

        Schema::create('training_content', function (Blueprint $table) {
            $table->id();
            $table->foreignId('training_content_group_id')
                ->references('id')
                ->on('training_content_group')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->foreignId('training_evaluation_group_id')
                ->nullable()
                ->references('id')
                ->on('training_evaluation_group')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->string('title');
            $table->longText('description')->nullable();
            $table->string('type');
            $table->boolean('is_visible');
            $table->dateTime('date_start')->nullable();
            $table->dateTime('date_limit')->nullable();
            $table->boolean('is_open');
            $table->decimal('score', 4, 2)->nullable();
            $table->boolean('has_evaluation_form');
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->dateTime('deleted_at')->nullable();
        });

        Schema::create('training_publication', function (Blueprint $table) {
            $table->id();
            $table->foreignId('training_id')
                ->references('id')
                ->on('training')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->foreignId('person_id')
                ->references('id')
                ->on('person')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->longText('value');
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->dateTime('deleted_at')->nullable();
        });


        Schema::create('training_comment', function (Blueprint $table) {
            $table->id();
            $table->morphs('commentable');
            $table->foreignId('person_id')
                ->references('id')
                ->on('person')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->longText('value');
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->dateTime('deleted_at')->nullable();
        });


        Schema::create('training_answer', function (Blueprint $table) {
            $table->id();
            $table->foreignId('person_id')
                ->references('id')
                ->on('person')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->foreignId('training_content_id')
                ->references('id')
                ->on('training_content')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->string('status');
            $table->decimal('score', 4, 2)->default(0.00);
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->dateTime('deleted_at')->nullable();
        });

        Schema::create('training_assistance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('person_id')
                ->references('id')
                ->on('person')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->foreignId('training_id')
                ->references('id')
                ->on('training')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->string('status')->nullable();
            $table->dateTime('date')->nullable();
            $table->text('reason')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->dateTime('deleted_at')->nullable();
        });

        Schema::create('training_question_type', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('name', 255);
            $table->string('data_type', 100);
            $table->integer('order_number');
            $table->json('options')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->dateTime('deleted_at')->nullable();
        });

        DB::table('training_question_type')->insert([
            [
                'id' => 1,
                'key' => 'OPTION',
                'name' => 'CASILLA DE VERIFICACIÓN',
                'data_type' => 'option',
                'order_number' => 1,
                'created_at' => $date,
            ],
            [
                'id' => 2,
                'key' => 'OPTION_MULTIPLE',
                'name' => 'SELECCIÓN MÚLTIPLE',
                'data_type' => 'option_multiple',
                'order_number' => 2,
                'created_at' => $date,
            ]
        ]);

        Schema::create('training_form', function (Blueprint $table) {
            $table->id();
            $table->foreignId('training_content_id')
                ->references('id')
                ->on('training_content')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->string('uuid')->unique();
            $table->string('title');
            $table->text('description')->nullable();
            $table->decimal('score_max', 4, 2);
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->dateTime('deleted_at')->nullable();
        });

        Schema::create('training_question', function (Blueprint $table) {
            $table->id();
            $table->foreignId('training_form_id')
                ->references('id')
                ->on('training_form')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->string('training_question_type_key');
            $table->string('key')->unique();
            $table->text('label');
            $table->integer('order_number');
            $table->decimal('score_max', 4, 2);
            $table->json('options');
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->dateTime('deleted_at')->nullable();
        });

        Schema::create('training_form_response', function (Blueprint $table) {
            $table->id();
            $table->foreignId('training_form_id')
                ->references('id')
                ->on('training_form')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->foreignId('person_id')
                ->references('id')
                ->on('person')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->json('questions');
            $table->decimal('score', 4, 2);
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
        Schema::dropIfExists('training_form_response');
        Schema::dropIfExists('training_question');
        Schema::dropIfExists('training_form');
        Schema::dropIfExists('training_question_type');
        Schema::dropIfExists('training_assistance');
        Schema::dropIfExists('training_answer');
        Schema::dropIfExists('training_comment');
        Schema::dropIfExists('training_publication');
        Schema::dropIfExists('training_content');
        Schema::dropIfExists('training_average_detail');
        Schema::dropIfExists('training_average');
        Schema::dropIfExists('training_content_group');
        Schema::dropIfExists('training_evaluation_group');
        Schema::dropIfExists('training_participant');
        Schema::dropIfExists('training_teacher');
        Schema::dropIfExists('training');
        Schema::dropIfExists('training_status');
        Schema::dropIfExists('training_category');
    }
};
