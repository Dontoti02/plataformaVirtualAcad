<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Copia local de las tres tablas de la BD central que se consultaban en el camino
 * crítico de cada petición: institution (suscripción), institution_module (módulos
 * contratados) e institution_storage (cuota de archivos).
 *
 * `institution` tiene exactamente UNA fila en modo single.
 * Ver ANALISIS-Y-PLAN.md §3.3 y §6.1.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('institution', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary();   // mismo id que en central
            $table->string('modular_code')->nullable();
            $table->string('ruc')->nullable();
            $table->string('name');
            $table->string('type_management')->nullable();
            $table->string('department')->nullable();
            $table->string('province')->nullable();
            $table->string('district')->nullable();
            $table->string('address')->nullable();
            $table->string('logo')->nullable();
            $table->boolean('is_active')->default(true);
            $table->dateTime('start_date')->nullable();
            $table->dateTime('end_date')->nullable();
            $table->timestamps();
        });

        Schema::create('institution_module', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('institution_id')->index();
            $table->string('name')->nullable();
            $table->string('module_key')->index();
            $table->boolean('is_active')->default(false);
            $table->dateTime('start_date')->nullable();
            $table->dateTime('end_date')->nullable();
            $table->json('settings')->nullable();
            $table->timestamps();

            $table->unique(['institution_id', 'module_key']);
        });

        Schema::create('institution_storage', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('institution_id')->index();
            $table->string('folder_name');
            $table->unsignedBigInteger('limit_space_mb')->default(0);
            $table->unsignedBigInteger('used_space_mb')->default(0);
            $table->unsignedBigInteger('free_space_mb')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('institution_storage');
        Schema::dropIfExists('institution_module');
        Schema::dropIfExists('institution');
    }
};
