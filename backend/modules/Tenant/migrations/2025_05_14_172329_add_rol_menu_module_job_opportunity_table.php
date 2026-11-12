<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
  /**
   * Run the migrations.
   */
  public function up(): void
  {
    $date = Carbon::now();

    $ROL_COMPANY_ID = DB::table('rol')->insertGetId([
      'name' => 'EMPRESA',
      'key' => 'rol_company',
      'level' => 1,
    ]);

    $ROL_ADMIN_ID =  DB::table('rol')->where('key', 'rol_admin')->first()->id;
    $ROL_STUDENT_ID = DB::table('rol')->where('key', 'rol_student')->first()->id;
    $ROL_TEACHER_ID = DB::table('rol')->where('key', 'rol_teacher')->first()->id;

    $MENU_BOLSA_LABORAL = DB::table('menu')->insertGetId([
      'name' => 'Bolsa Laboral',
      'created_at' => $date,
      'updated_at' => $date,
    ]);

    //opciones
    $OPTION_OFFERS_ID = DB::table('option')->insertGetId([
      'menu_id' => $MENU_BOLSA_LABORAL,
      'name' => 'Ofertas laborales',
      'name_url' => 'Offers',
      'created_at' => $date,
      'updated_at' => $date,
    ]);

    $OPTION_COMPANIES_ID = DB::table('option')->insertGetId([
      'menu_id' => $MENU_BOLSA_LABORAL,
      'name' => 'Empresas',
      'name_url' => 'Companies',
      'created_at' => $date,
      'updated_at' => $date,
    ]);

    $OPTION_POSTULACIONES_ID = DB::table('option')->insertGetId([
      'menu_id' => $MENU_BOLSA_LABORAL,
      'name' => 'Postulaciones',
      'name_url' => 'Applications',
      'created_at' => $date,
      'updated_at' => $date,
    ]);
    
    $OPTION_CANDIDATE_ID = DB::table('option')->insertGetId([
      'menu_id' => $MENU_BOLSA_LABORAL,
      'name' => 'Candidato',
      'name_url' => 'Candidate',
      'created_at' => $date,
      'updated_at' => $date,
    ]);

    $OPTION_MAINTAINERS_ID = DB::table('option')->insertGetId([
      'menu_id' => $MENU_BOLSA_LABORAL,
      'name' => 'Mantenedores',
      'name_url' => 'JobMaintainers',
      'created_at' => $date,
      'updated_at' => $date,
    ]);


    // creacion de rol_option para el rol administrador
    DB::table('rol_option')->insert([
      'rol_id' => $ROL_ADMIN_ID,
      'option_id' => $OPTION_COMPANIES_ID,
    ]);

    DB::table('rol_option')->insert([
      'rol_id' => $ROL_ADMIN_ID,
      'option_id' => $OPTION_OFFERS_ID,
    ]);

    DB::table('rol_option')->insert([
      'rol_id' => $ROL_ADMIN_ID,
      'option_id' => $OPTION_POSTULACIONES_ID,
    ]);
    DB::table('rol_option')->insert([
      'rol_id' => $ROL_ADMIN_ID,
      'option_id' => $OPTION_MAINTAINERS_ID,
    ]);

    // creacion de rol_option para el rol de empresa
    DB::table('rol_option')->insert([
      'rol_id' => $ROL_COMPANY_ID,
      'option_id' => 1, // home
    ]);

    DB::table('rol_option')->insert([
      'rol_id' => $ROL_COMPANY_ID,
      'option_id' => $OPTION_OFFERS_ID,
    ]);

    DB::table('rol_option')->insert([
      'rol_id' => $ROL_COMPANY_ID,
      'option_id' => $OPTION_POSTULACIONES_ID,
    ]);

    DB::table('rol_option')->insert([
      'rol_id' => $ROL_COMPANY_ID,
      'option_id' => 12, // profile
    ]);

    // creacion de rol_option para el rol de estudiante
    DB::table('rol_option')->insert([
      'rol_id' => $ROL_STUDENT_ID,
      'option_id' => $OPTION_CANDIDATE_ID,
    ]);

    // creacion de rol_option para el rol de docente
    DB::table('rol_option')->insert([
      'rol_id' => $ROL_TEACHER_ID,
      'option_id' => $OPTION_CANDIDATE_ID,
    ]);
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    //
  }
};
