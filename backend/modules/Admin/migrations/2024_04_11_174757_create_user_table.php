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
        Schema::create('rol', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->dateTime('deleted_at')->nullable();
        });

        Schema::create('user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rol_id')
                ->nullable()
                ->references('id')
                ->on('rol')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('names');
            $table->rememberToken();
            $table->string('reset_password_token')->nullable();
            $table->boolean('is_active')->default(true);
            $table->dateTime('last_login')->nullable();
            $table->text('avatar')->nullable();
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->dateTime('last_attempt')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->dateTime('deleted_at')->nullable();
        });

        Schema::create('rol_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rol_id')
                ->references('id')
                ->on('rol')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->foreignId('user_id')
                ->references('id')
                ->on('user')
                ->onUpdate('cascade')
                ->onDelete('cascade');
        });

        Schema::create('menu', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->dateTime('deleted_at')->nullable();
        });

        Schema::create('option', function (Blueprint $table) {
            $table->id();
            $table->foreignId('option_id')
                ->nullable()
                ->references('id')
                ->on('option')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->foreignId('menu_id')
                ->nullable()
                ->references('id')
                ->on('menu')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->string('name');
            $table->string('name_url');
            $table->string('icon')->nullable();
            $table->boolean('is_visible')->default(true);
            $table->integer('order');
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->dateTime('deleted_at')->nullable();
        });

        Schema::create('rol_option', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rol_id')
                ->references('id')
                ->on('rol')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->foreignId('option_id')
                ->references('id')
                ->on('option')
                ->onUpdate('cascade')
                ->onDelete('cascade');
        });

        $date = Carbon::now();

        $rol_id_admin = DB::table('rol')->insertGetId([
            'name' => 'ADMINISTRADOR',
            'created_at' => $date,
            'updated_at' => $date,
        ]);

        $menu_id_general = DB::table('menu')->insertGetId([
            'name' => 'GENERAL',
            'created_at' => $date,
            'updated_at' => $date,
        ]);

        $option_id_home = DB::table('option')->insertGetId([
            'menu_id' => $menu_id_general,
            'name' => 'Inicio',
            'name_url' => 'Home',
            'icon' => 'mdi-view-dashboard',
            'order' => 1,
            'created_at' => $date,
            'updated_at' => $date,
        ]);
        $option_id_institution = DB::table('option')->insertGetId([
            'menu_id' => $menu_id_general,
            'name' => 'Instituciones',
            'name_url' => 'Institution',
            'icon' => 'mdi-office-building',
            'order' => 2,
            'created_at' => $date,
            'updated_at' => $date,
        ]);
        $option_id_users = DB::table('option')->insertGetId([
            'menu_id' => $menu_id_general,
            'name' => 'Usuarios',
            'name_url' => 'Users',
            'icon' => 'mdi-account-multiple',
            'order' => 3,
            'created_at' => $date,
            'updated_at' => $date,
        ]);
        $option_id_parameters = DB::table('option')->insertGetId([
            'menu_id' => $menu_id_general,
            'name' => 'Parámetros',
            'name_url' => 'Parameters',
            'icon' => 'mdi-cogs',
            'order' => 4,
            'created_at' => $date,
            'updated_at' => $date,
        ]);
        $option_id_profile = DB::table('option')->insertGetId([
            'menu_id' => $menu_id_general,
            'name' => 'Mi perfil',
            'name_url' => 'Profile',
            'icon' => 'mdi-account-outline',
            'is_visible' => false,
            'order' => 5,
            'created_at' => $date,
            'updated_at' => $date,
        ]);

        DB::table('rol_option')->insert([
            'rol_id' => $rol_id_admin,
            'option_id' => $option_id_home,
        ]);
        DB::table('rol_option')->insert([
            'rol_id' => $rol_id_admin,
            'option_id' => $option_id_institution,
        ]);
        DB::table('rol_option')->insert([
            'rol_id' => $rol_id_admin,
            'option_id' => $option_id_users,
        ]);
        DB::table('rol_option')->insert([
            'rol_id' => $rol_id_admin,
            'option_id' => $option_id_parameters,
        ]);
        DB::table('rol_option')->insert([
            'rol_id' => $rol_id_admin,
            'option_id' => $option_id_profile,
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user');
        Schema::dropIfExists('rol');
        Schema::dropIfExists('rol_user');
        Schema::dropIfExists('menu');
        Schema::dropIfExists('option');
        Schema::dropIfExists('rol_option');
    }
};
