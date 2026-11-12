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
        Schema::create('system_configuration', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('name')->unique();
            $table->string('type');
            $table->longText('value')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->dateTime('deleted_at')->nullable();
        });

        $date = Carbon::now();

        DB::table('system_configuration')->insert([
            'key' => 'application_name',
            'name' => 'Nombre del sistema',
            'type' => 'string',
            'value' => 'Latina Educa',
            'created_at' => $date,
            'updated_at' => $date,
        ]);
        DB::table('system_configuration')->insert([
            'key' => 'domain',
            'name' => 'Dominio',
            'type' => 'string',
            'value' => 'latinaeduca.com',
            'created_at' => $date,
            'updated_at' => $date,
        ]);
        DB::table('system_configuration')->insert([
            'key' => 'logo',
            'name' => 'Logo',
            'type' => 'string',
            'value' => null,
            'created_at' => $date,
            'updated_at' => $date,
        ]);
        DB::table('system_configuration')->insert([
            'key' => 'favicon',
            'name' => 'Favicon',
            'type' => 'string',
            'value' => null,
            'created_at' => $date,
            'updated_at' => $date,
        ]);
        DB::table('system_configuration')->insert([
            'key' => 'banner',
            'name' => 'Banner',
            'type' => 'string',
            'value' => null,
            'created_at' => $date,
            'updated_at' => $date,
        ]);
        DB::table('system_configuration')->insert([
            'key' => 'user_default_auth_institution',
            'name' => 'Usuario por defecto a crearse en cada institución',
            'type' => 'json',
            'value' => json_encode([
                'email' => 'centrodeinvestigacion@gmail.com',
                'password' => '98c9bff2-1fcb-4c29-b7f1-358a43be5405'
            ]),
            'created_at' => $date,
            'updated_at' => $date,
        ]);
        DB::table('system_configuration')->insert([
            'key' => 'default_limit_space_institution_mb',
            'name' => 'Tamaño de almacenamiento por defecto para las instituciones',
            'type' => 'number',
            'value' => 51200,
            'created_at' => $date,
            'updated_at' => $date,
        ]);
        // DB::table('system_configuration')->insert([
        //     'key' => 'igv_amount',
        //     'name' => 'Impuesto general a las ventas aplicado a los conceptos de pago',
        //     'type' => 'number',
        //     'value' => 0.18,
        //     'created_at' => $date,
        //     'updated_at' => $date,
        // ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_configuration');
    }
};
