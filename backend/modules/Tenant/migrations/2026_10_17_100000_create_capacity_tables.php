<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Capacidades e indicadores: el mismo módulo que la línea actual, adaptado a
 * esta base.
 *
 * Diferencias con la línea actual, a propósito:
 * - Las notas por indicador y capacidad van por persona (person_id), como
 *   average y participant aquí. En esta línea `student` es una fila por
 *   periodo, programa y ciclo, no una por persona.
 * - capacity.evaluation_group_id marca la capacidad que salió de cada
 *   competencia: es lo que hace la migración de datos repetible sin duplicar.
 * - Las notas llevan clave única (persona, indicador/capacidad) por lo mismo.
 * - Nada se borra: evaluation_group, average y average_detail se conservan.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('capacity')) {
            Schema::create('capacity', function (Blueprint $table) {
                $table->id();
                $table->foreignId('classroom_id')
                    ->references('id')
                    ->on('classroom')
                    ->onUpdate('cascade')
                    ->onDelete('cascade');
                $table->unsignedBigInteger('evaluation_group_id')->nullable()->unique();
                $table->text('name');
                $table->smallInteger('order');
                $table->decimal('weight', 3, 2)->nullable();
                $table->dateTime('created_at')->nullable();
                $table->dateTime('updated_at')->nullable();
                $table->dateTime('deleted_at')->nullable();
            });
        }

        if (! Schema::hasTable('indicator')) {
            Schema::create('indicator', function (Blueprint $table) {
                $table->id();
                $table->foreignId('capacity_id')
                    ->references('id')
                    ->on('capacity')
                    ->onUpdate('cascade')
                    ->onDelete('cascade');
                $table->text('name');
                $table->smallInteger('order');
                $table->dateTime('created_at')->nullable();
                $table->dateTime('updated_at')->nullable();
                $table->dateTime('deleted_at')->nullable();
            });
        }

        if (! Schema::hasTable('student_indicator')) {
            Schema::create('student_indicator', function (Blueprint $table) {
                $table->id();
                $table->foreignId('person_id')
                    ->references('id')
                    ->on('person')
                    ->onUpdate('cascade')
                    ->onDelete('cascade');
                $table->foreignId('indicator_id')
                    ->references('id')
                    ->on('indicator')
                    ->onUpdate('cascade')
                    ->onDelete('cascade');
                $table->decimal('score', 4, 2)->default(0.00);
                $table->dateTime('created_at')->nullable();
                $table->dateTime('updated_at')->nullable();
                $table->unique(['person_id', 'indicator_id']);
            });
        }

        if (! Schema::hasTable('student_capacity')) {
            Schema::create('student_capacity', function (Blueprint $table) {
                $table->id();
                $table->foreignId('person_id')
                    ->references('id')
                    ->on('person')
                    ->onUpdate('cascade')
                    ->onDelete('cascade');
                $table->foreignId('capacity_id')
                    ->references('id')
                    ->on('capacity')
                    ->onUpdate('cascade')
                    ->onDelete('cascade');
                $table->decimal('score', 4, 2)->default(0.00);
                $table->decimal('score_by_weight', 4, 2)->nullable();
                $table->decimal('recover_score', 4, 2)->nullable();
                $table->dateTime('created_at')->nullable();
                $table->dateTime('updated_at')->nullable();
                $table->unique(['person_id', 'capacity_id']);
            });
        }

        if (! Schema::hasColumn('content', 'indicator_id')) {
            Schema::table('content', function (Blueprint $table) {
                // nullOnDelete, no cascade: borrar un indicador nunca debe
                // llevarse contenidos (y con ellos las respuestas y sus notas).
                $table->foreignId('indicator_id')
                    ->nullable()
                    ->after('evaluation_group_id')
                    ->references('id')
                    ->on('indicator')
                    ->onUpdate('cascade')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('content', 'indicator_id')) {
            Schema::table('content', function (Blueprint $table) {
                $table->dropForeign(['indicator_id']);
                $table->dropColumn('indicator_id');
            });
        }

        Schema::dropIfExists('student_capacity');
        Schema::dropIfExists('student_indicator');
        Schema::dropIfExists('indicator');
        Schema::dropIfExists('capacity');
    }
};
