<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Configuración del correo saliente de la institución.
 *
 * En un VPS por institución cada una envía con su propia cuenta (Gmail o
 * Workspace, normalmente) y la configura ella misma en Ajustes del sistema.
 * Arranca DESACTIVADO: hasta que la institución lo configure, los correos se
 * escriben en el log en vez de enviarse.
 *
 * Tabla propia y no filas en system_configuration: el listado genérico de
 * parámetros se envía entero al frontend, y la contraseña no debe ir ahí.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('mail_settings')) {
            return;
        }

        Schema::create('mail_settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('enabled')->default(false);
            $table->string('host')->nullable();
            $table->unsignedSmallInteger('port')->nullable();
            $table->string('encryption', 10)->nullable();   // tls | ssl | none
            $table->string('username')->nullable();
            $table->text('password')->nullable();            // cifrada con el APP_KEY
            $table->string('from_address')->nullable();
            $table->string('from_name')->nullable();
            $table->timestamps();
        });

        // Valores de partida para Gmail, el caso habitual. Desactivado.
        DB::table('mail_settings')->insert([
            'enabled'    => false,
            'host'       => 'smtp.gmail.com',
            'port'       => 587,
            'encryption' => 'tls',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('mail_settings');
    }
};
