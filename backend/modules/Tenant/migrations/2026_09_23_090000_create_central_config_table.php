<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Snapshot local de la configuración global que antes se consultaba en vivo
 * contra la BD central en cada petición.  Ver ANALISIS-Y-PLAN.md §3.2.
 *
 * Se rellena con `php artisan central:sync` y se refresca por cron o webhook.
 * Si la central no responde, la institución sigue operando con lo último válido.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('central_config', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('type', 20)->default('string');   // string|number|json|array|bool
            $table->longText('value')->nullable();
            $table->timestamp('synced_at')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('central_config');
    }
};
