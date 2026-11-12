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
            $table->foreignId('person_id')
                ->references('id')
                ->on('person')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->foreignId('rol_id')
                ->nullable()
                ->references('id')
                ->on('rol')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->string('email')->unique();
            $table->string('password');
            $table->rememberToken();
            $table->string('reset_password_token')->nullable();
            $table->boolean('default_user')->default(false);
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
                ->references('id')
                ->on('menu')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->string('name');
            $table->string('name_url');
            $table->string('icon')->nullable();
            $table->boolean('is_visible')->default(true);
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

        // initial roles

        $rol_id_secretary = DB::table('rol')->insertGetId([
            'name' => 'SECRETARIO ACADÉMICO',
            'created_at' => $date,
            'updated_at' => $date,
        ]);
        $rol_id_teacher = DB::table('rol')->insertGetId([
            'name' => 'DOCENTE',
            'created_at' => $date,
            'updated_at' => $date,
        ]);
        $rol_id_student = DB::table('rol')->insertGetId([
            'name' => 'ESTUDIANTE',
            'created_at' => $date,
            'updated_at' => $date,
        ]);
        $rol_id_admin = DB::table('rol')->insertGetId([
            'name' => 'ADMINISTRADOR',
            'created_at' => $date,
            'updated_at' => $date,
        ]);

        // menus

        $menu_id_principal = DB::table('menu')->insertGetId([
            'name' => 'Principal',
            'created_at' => $date,
            'updated_at' => $date,
        ]);
        $menu_id_aula_virtual = DB::table('menu')->insertGetId([
            'name' => 'Aula virtual',
            'created_at' => $date,
            'updated_at' => $date,
        ]);
        $menu_id_preferencias = DB::table('menu')->insertGetId([
            'name' => 'Preferencias',
            'created_at' => $date,
            'updated_at' => $date,
        ]);
        $menu_id_configuration = DB::table('menu')->insertGetId([
            'name' => 'Configuración',
            'created_at' => $date,
            'updated_at' => $date,
        ]);
        $menu_id_quick_access = DB::table('menu')->insertGetId([
            'name' => 'Acceso rápido',
            'created_at' => $date,
            'updated_at' => $date,
        ]);
        $menu_id_academic_processes = DB::table('menu')->insertGetId([
            'name' => 'Procesos Académicos',
            'created_at' => $date,
            'updated_at' => $date,
        ]);
        
        // options

        $option_id_home = DB::table('option')->insertGetId([
            'menu_id' => $menu_id_principal,
            'name' => 'Inicio',
            'name_url' => 'Home',
            'created_at' => $date,
            'updated_at' => $date,
        ]);
        $option_id_my_courses = DB::table('option')->insertGetId([
            'menu_id' => $menu_id_aula_virtual,
            'name' => 'Mis cursos',
            'name_url' => 'MyCourses',
            'created_at' => $date,
            'updated_at' => $date,
        ]);
        $option_id_settings = DB::table('option')->insertGetId([
            'menu_id' => $menu_id_preferencias,
            'name' => 'Ajustes',
            'name_url' => 'Settings',
            'created_at' => $date,
            'updated_at' => $date,
        ]);
        $option_id_current_courses = DB::table('option')->insertGetId([
            'option_id' => $option_id_my_courses,
            'menu_id' => $menu_id_aula_virtual,
            'name' => 'Actuales',
            'name_url' => 'CurrentCourses',
            'created_at' => $date,
            'updated_at' => $date,
        ]);
        $option_id_archived_courses = DB::table('option')->insertGetId([
            'option_id' => $option_id_my_courses,
            'menu_id' => $menu_id_aula_virtual,
            'name' => 'Archivados',
            'name_url' => 'ArchivedCourses',
            'created_at' => $date,
            'updated_at' => $date,
        ]);
        $option_id_schedules = DB::table('option')->insertGetId([
            'menu_id' => $menu_id_configuration,
            'name' => 'Horarios',
            'name_url' => 'Schedules',
            'created_at' => $date,
            'updated_at' => $date,
        ]);
        $option_id_users = DB::table('option')->insertGetId([
            'menu_id' => $menu_id_configuration,
            'name' => 'Usuarios',
            'name_url' => 'UsersList',
            'created_at' => $date,
            'updated_at' => $date,
        ]);
        $option_id_profile = DB::table('option')->insertGetId([
            'menu_id' => $menu_id_quick_access,
            'name' => 'Mi Perfil',
            'name_url' => 'Profile',
            'created_at' => $date,
            'updated_at' => $date,
        ]);
        $option_id_import = DB::table('option')->insertGetId([
            'menu_id' => $menu_id_configuration,
            'name' => 'Importación',
            'name_url' => 'Importation',
            'created_at' => $date,
            'updated_at' => $date,
        ]);
        $option_id_enrollment = DB::table('option')->insertGetId([
            'menu_id' => $menu_id_academic_processes,
            'name' => 'Matrículas',
            'name_url' => 'Enrollment',
            'created_at' => $date,
            'updated_at' => $date,
        ]);
        $option_id_academy_history = DB::table('option')->insertGetId([
            'menu_id' => $menu_id_academic_processes,
            'name' => 'Historial académico',
            'name_url' => 'AcademicHistory',
            'created_at' => $date,
            'updated_at' => $date,
        ]);
        $option_id_curriculum = DB::table('option')->insertGetId([
            'menu_id' => $menu_id_academic_processes,
            'name' => 'Plan de estudios',
            'name_url' => 'Curriculum',
            'created_at' => $date,
            'updated_at' => $date,
        ]);
        $option_id_landing_page = DB::table('option')->insertGetId([
            'menu_id' => $menu_id_configuration,
            'name' => 'Landing Page',
            'name_url' => 'LandingPage',
            'created_at' => $date,
            'updated_at' => $date,
        ]);
        $option_id_academic_periods = DB::table('option')->insertGetId([
            'menu_id' => $menu_id_academic_processes,
            'name' => 'Periodos Académicos',
            'name_url' => 'AcademicPeriods',
            'created_at' => $date,
            'updated_at' => $date,
        ]);
        $option_id_list_of_merit = DB::table('option')->insertGetId([
            'menu_id' => $menu_id_academic_processes,
            'name' => 'Lista de mérito',
            'name_url' => 'ListOfMerit',
            'created_at' => $date,
            'updated_at' => $date,
        ]);


        // roles options
     
        DB::table('rol_option')->insert([
            'rol_id' => $rol_id_secretary,
            'option_id' => $option_id_home,
        ]);
        DB::table('rol_option')->insert([
            'rol_id' => $rol_id_secretary,
            'option_id' => $option_id_settings,
        ]);
        DB::table('rol_option')->insert([
            'rol_id' => $rol_id_secretary,
            'option_id' => $option_id_schedules,
        ]);
        DB::table('rol_option')->insert([
            'rol_id' => $rol_id_secretary,
            'option_id' => $option_id_users,
        ]);
        DB::table('rol_option')->insert([
            'rol_id' => $rol_id_secretary,
            'option_id' => $option_id_profile,
        ]);
        DB::table('rol_option')->insert([
            'rol_id' => $rol_id_secretary,
            'option_id' => $option_id_import,
        ]);
        DB::table('rol_option')->insert([
            'rol_id' => $rol_id_secretary,
            'option_id' => $option_id_enrollment,
        ]);
        DB::table('rol_option')->insert([
            'rol_id' => $rol_id_secretary,
            'option_id' => $option_id_academy_history,
        ]);
        DB::table('rol_option')->insert([
            'rol_id' => $rol_id_secretary,
            'option_id' => $option_id_curriculum,
        ]);
        DB::table('rol_option')->insert([
            'rol_id' => $rol_id_secretary,
            'option_id' => $option_id_landing_page,
        ]);
        DB::table('rol_option')->insert([
            'rol_id' => $rol_id_secretary,
            'option_id' => $option_id_academic_periods,
        ]);
        DB::table('rol_option')->insert([
            'rol_id' => $rol_id_secretary,
            'option_id' => $option_id_list_of_merit,
        ]);
        
        DB::table('rol_option')->insert([
            'rol_id' => $rol_id_teacher,
            'option_id' => $option_id_home,
        ]);
        DB::table('rol_option')->insert([
            'rol_id' => $rol_id_teacher,
            'option_id' => $option_id_my_courses,
        ]);
        DB::table('rol_option')->insert([
            'rol_id' => $rol_id_teacher,
            'option_id' => $option_id_current_courses,
        ]);
        DB::table('rol_option')->insert([
            'rol_id' => $rol_id_teacher,
            'option_id' => $option_id_archived_courses,
        ]);
        DB::table('rol_option')->insert([
            'rol_id' => $rol_id_teacher,
            'option_id' => $option_id_schedules,
        ]);
        DB::table('rol_option')->insert([
            'rol_id' => $rol_id_teacher,
            'option_id' => $option_id_profile,
        ]);

        DB::table('rol_option')->insert([
            'rol_id' => $rol_id_student,
            'option_id' => $option_id_home,
        ]);
        DB::table('rol_option')->insert([
            'rol_id' => $rol_id_student,
            'option_id' => $option_id_my_courses,
        ]);
        DB::table('rol_option')->insert([
            'rol_id' => $rol_id_student,
            'option_id' => $option_id_current_courses,
        ]);
        DB::table('rol_option')->insert([
            'rol_id' => $rol_id_student,
            'option_id' => $option_id_archived_courses,
        ]);
        DB::table('rol_option')->insert([
            'rol_id' => $rol_id_student,
            'option_id' => $option_id_schedules,
        ]);
        DB::table('rol_option')->insert([
            'rol_id' => $rol_id_student,
            'option_id' => $option_id_profile,
        ]);
        DB::table('rol_option')->insert([
            'rol_id' => $rol_id_student,
            'option_id' => $option_id_enrollment,
        ]);
        DB::table('rol_option')->insert([
            'rol_id' => $rol_id_student,
            'option_id' => $option_id_academy_history,
        ]);
        DB::table('rol_option')->insert([
            'rol_id' => $rol_id_student,
            'option_id' => $option_id_curriculum,
        ]);

        DB::table('rol_option')->insert([
            'rol_id' => $rol_id_admin,
            'option_id' => $option_id_home,
        ]);
        DB::table('rol_option')->insert([
            'rol_id' => $rol_id_admin,
            'option_id' => $option_id_settings,
        ]);
        DB::table('rol_option')->insert([
            'rol_id' => $rol_id_admin,
            'option_id' => $option_id_users,
        ]);
        DB::table('rol_option')->insert([
            'rol_id' => $rol_id_admin,
            'option_id' => $option_id_profile,
        ]);
        DB::table('rol_option')->insert([
            'rol_id' => $rol_id_admin,
            'option_id' => $option_id_import,
        ]);
        DB::table('rol_option')->insert([
            'rol_id' => $rol_id_admin,
            'option_id' => $option_id_landing_page,
        ]);
        DB::table('rol_option')->insert([
            'rol_id' => $rol_id_admin,
            'option_id' => $option_id_academic_periods,
        ]);
        DB::table('rol_option')->insert([
            'rol_id' => $rol_id_admin,
            'option_id' => $option_id_list_of_merit,
        ]);
        DB::table('rol_option')->insert([
            'rol_id' => $rol_id_admin,
            'option_id' => $option_id_schedules,
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
