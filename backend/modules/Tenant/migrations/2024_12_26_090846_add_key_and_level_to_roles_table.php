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

    Schema::table('rol', function (Blueprint $table) {
      $table->string('key')->after('name')->nullable();
      $table->integer('level')->default(1)->after('key');
    });

    DB::table('rol')->where('id', 1)->update([
      'key' => 'rol_academic_secretary',
      'level' => 1,
    ]);

    DB::table('rol')->where('id', 2)->update([
      'key' => 'rol_teacher',
      'level' => 1,
    ]);

    DB::table('rol')->where('id', 3)->update([
      'key' => 'rol_student',
      'level' => 1,
    ]);

    DB::table('rol')->where('id', 4)->update([
      'key' => 'rol_admin',
      'level' => 1,
    ]);


    // nuevos roles nivel 2
    $rol_id_admin_training = DB::table('rol')->insertGetId([
      'name' => 'COORDINADOR DE CAPACITACIONES',
      'key' => 'rol_admin_training',
      'level' => 2,
    ]);

    $rol_id_teacher_training = DB::table('rol')->insertGetId([
      'name' => 'DOCENTE DE CAPACITACIONES',
      'key' => 'rol_teacher_training',
      'level' => 2,
    ]);

    $rol_id_student_training = DB::table('rol')->insertGetId([
      'name' => 'ESTUDIANTE DE CAPACITACIONES',
      'key' => 'rol_student_training',
      'level' => 2,
    ]);

    // Nuevo menu
    $menu_id_training = DB::table('menu')->insertGetId([
      'name' => 'Capacitaciones',
      'created_at' => $date,
      'updated_at' => $date,
    ]);

    // Nuevas opciones
    $option_id_training_list = DB::table('option')->insertGetId([
      'menu_id' => $menu_id_training,
      'name' => 'Mis Capacitaciones',
      'name_url' => 'AdminCapacitationList',
      'created_at' => $date,
      'updated_at' => $date,
    ]);

    $option_id_training_report = DB::table('option')->insertGetId([
      'menu_id' => $menu_id_training,
      'name' => 'Reportes',
      'name_url' => 'ReportCapacitation',
      'created_at' => $date,
      'updated_at' => $date,
    ]);

    $option_id_training_create = DB::table('option')->insertGetId([
      'menu_id' => $menu_id_training,
      'name' => 'Crear capacitaciones',
      'name_url' => 'ManageCapacitation',
      'created_at' => $date,
      'updated_at' => $date,
    ]);

    $option_id_training_student_list = DB::table('option')->insertGetId([
      'menu_id' => $menu_id_training,
      'name' => 'Listado de estudiantes',
      'name_url' => 'CapacitationStudents',
      'created_at' => $date,
      'updated_at' => $date,
    ]);

    $option_id_training_content = DB::table('option')->insertGetId([
      'menu_id' => $menu_id_training,
      'name' => 'Contenido de capacitaciones',
      'name_url' => 'CurrentTraining',
      'created_at' => $date,
      'updated_at' => $date,
    ]);

    $option_id_home = DB::table('option')->where('name_url', 'Home')->value('id');
    $option_id_profile = DB::table('option')->where('name_url', 'Profile')->value('id');

    // Student
    DB::table('rol_option')->insert([
      'rol_id' => $rol_id_student_training,
      'option_id' => $option_id_home,
    ]);
    DB::table('rol_option')->insert([
      'rol_id' => $rol_id_student_training,
      'option_id' => $option_id_profile,
    ]);
    DB::table('rol_option')->insert([
      'rol_id' => $rol_id_student_training,
      'option_id' => $option_id_training_list,
    ]);
    DB::table('rol_option')->insert([
      'rol_id' => $rol_id_student_training,
      'option_id' => $option_id_training_content,
    ]);

    // Teacher
    DB::table('rol_option')->insert([
      'rol_id' => $rol_id_teacher_training,
      'option_id' => $option_id_home,
    ]);
    DB::table('rol_option')->insert([
      'rol_id' => $rol_id_teacher_training,
      'option_id' => $option_id_profile,
    ]);
    DB::table('rol_option')->insert([
      'rol_id' => $rol_id_teacher_training,
      'option_id' => $option_id_training_list,
    ]);
    DB::table('rol_option')->insert([
      'rol_id' => $rol_id_teacher_training,
      'option_id' => $option_id_training_content,
    ]);

    // Admin
    DB::table('rol_option')->insert([
      'rol_id' => $rol_id_admin_training,
      'option_id' => $option_id_home,
    ]);
    DB::table('rol_option')->insert([
      'rol_id' => $rol_id_admin_training,
      'option_id' => $option_id_profile,
    ]);
    DB::table('rol_option')->insert([
      'rol_id' => $rol_id_admin_training,
      'option_id' => $option_id_training_list,
    ]);
    DB::table('rol_option')->insert([
      'rol_id' => $rol_id_admin_training,
      'option_id' => $option_id_training_create,
    ]);
    DB::table('rol_option')->insert([
      'rol_id' => $rol_id_admin_training,
      'option_id' => $option_id_training_student_list,
    ]);
    DB::table('rol_option')->insert([
      'rol_id' => $rol_id_admin_training,
      'option_id' => $option_id_training_report,
    ]);


    // ENROLLMENT

    $rol_id_secretary = 1;

    //menu 

    $enroll_id= DB::table('menu')->insertGetId([
        'name' => 'Tesorería',
        'created_at' => $date,
        'updated_at' => $date,
    ]);
    
    // option 

    $option_id_enroll_student= DB::table('option')->insertGetId([
        'menu_id' => $enroll_id,
        'name' => 'Matricular Estudiante',
        'name_url' => 'enrollStudent',
        'created_at' => $date,
        'updated_at' => $date,
    ]);

    $option_id_enroll_list= DB::table('option')->insertGetId(([
        'menu_id' => $enroll_id,
        'name' => 'Matriculas Registradas',
        'name_url' => 'enrollList',
        'created_at' => $date,
        'updated_at' => $date,
    ]));

    $option_id_scale = DB::table('option')->insertGetId([
      'menu_id' => $enroll_id,
      'name' => 'Escalas',
      'name_url' => 'Scales',
      'created_at' => $date,
      'updated_at' => $date,
    ]);

    $option_id_enroll_payment_concepts = DB::table('option')->insertGetId([
        'menu_id' => $enroll_id,
        'name' => 'Conceptos de Pago',
        'name_url' => 'PaymentConcepts',
        'created_at' => $date,
        'updated_at' => $date,
    ]);

    $option_id_payments = DB::table('option')->insertGetId([
      'menu_id' => $enroll_id,
      'name' => 'Pagos',
      'name_url' => 'payments',
      'created_at' => $date,
      'updated_at' => $date,
    ]);


    // rol option
    DB::table('rol_option')->insert([
        'rol_id' => $rol_id_secretary,
        'option_id' => $option_id_enroll_student,
    ]);

    DB::table('rol_option')->insert([
        'rol_id'=> $rol_id_secretary,
        'option_id'=>$option_id_enroll_list
    ]);

    DB::table('rol_option')->insert([
        'rol_id' => $rol_id_secretary,
        'option_id' => $option_id_enroll_payment_concepts,
    ]);

    DB::table('rol_option')->insert([
      'rol_id' => $rol_id_secretary,
      'option_id' => $option_id_scale,
    ]);

    DB::table('rol_option')->insert([
      'rol_id' => $rol_id_secretary,
      'option_id' => $option_id_payments,
    ]);
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('rol', function (Blueprint $table) {
      $table->dropColumn(['key', 'level']);
    });
  }
};
