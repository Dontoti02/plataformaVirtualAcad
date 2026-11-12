<?php

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
        Schema::create('import', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('title');
            $table->json('attributes');
        });

        DB::table('import')->insert([
            'key' => 'staff',
            'title' => 'REPORTE DE PERSONAL',
            'attributes' => json_encode([
                "period" => "PERIODO LECTIVO",
                "document_type" => "TIPO DOCUMENTO",
                "document_number" => "DOCUMENTO",
                "names" => "NOMBRES Y APELLIDOS",
                "phone" => "CELULAR",
                "email" => "CORREO",
                "type" => "TIPO PERSONAL",
                "registration_date" => "FECHA REGISTRO",
                "working_condition" => "CONDICIÓN LABORAL"
            ]),
        ]);

        DB::table('import')->insert([
            'key' => 'study_programs',
            'title' => 'REPORTE DE PROGRAMAS DE ESTUDIOS',
            'attributes' => json_encode([
                "period" => "PERIODO LECTIVO",
                "name_program" => "PROGRAMA DE ESTUDIOS",
                "name_productive_family" => "FAMILIA PRODUCTIVA",
                "name_plan" => "PLAN DE ESTUDIOS",
                "type" => "TIPO PLAN",
            ]),
        ]);

        // DB::table('import')->insert([
        //     'key' => 'admissions',
        //     'title' => 'REPORTE DE ADMISIÓN',
        //     'attributes' => json_encode([
        //         "period" => "LECTIVO",
        //         // institution
        //         "department" => "REGIÓN",
        //         "province" => "PROVINCIA",
        //         "district" => "DISTRITO",
        //         "modular_code" => "CÓDIGO MODULAR",
        //         "name_institution" => "NOMBRE INSTITUCIÓN",
        //         "type_management" => "TIPO GESTIÓN",
        //         // postulant
        //         "document_type" => "DATOS DE POSTULANTE.TIPO DOCUMENTO",
        //         "document_number" => "DATOS DE POSTULANTE.DOCUMENTO",
        //         "last_name_one" => "DATOS DE POSTULANTE.APELLIDO PATERNO",
        //         "last_name_two" => "DATOS DE POSTULANTE.APELLIDO MATERNO",
        //         "name" => "DATOS DE POSTULANTE.NOMBRES",
        //         "sex" => "DATOS DE POSTULANTE.SEXO",
        //         "birth_date" => "DATOS DE POSTULANTE.FECHA NACIMIENTO",
        //         "native_language" => "DATOS DE POSTULANTE.LENGUA MATERNA",
        //         // institution of origin
        //         // "" => "DATOS DE IE BÁSICA DE PROCEDENCIA.CÓDIGO UBIGEO",
        //         // "" => "DATOS DE IE BÁSICA DE PROCEDENCIA.REGIÓN UBIGEO IE",
        //         // "" => "DATOS DE IE BÁSICA DE PROCEDENCIA.TIPO INSTITUCIÓN IE",
        //         // "" => "DATOS DE IE BÁSICA DE PROCEDENCIA.CÓDIGO MODULAR IE ",
        //         // "" => "DATOS DE IE BÁSICA DE PROCEDENCIA.NOMBRE IE",
        //         // "" => "DATOS DE IE BÁSICA DE PROCEDENCIA.TIPO GESTIÓN IE ",
        //         // "" => "DATOS DE IE BÁSICA DE PROCEDENCIA.AÑO DE EGRESO",
        //         //sede
        //         "name_sede" => "SEDE",
        //         // study_program
        //         "name_program" => "PROGRAMA DE ESTUDIOS",
        //         // postulation
        //         // "" => "DATOS DE POSTULACIÓN.MODALIDAD",
        //         // "" => "DATOS DE POSTULACIÓN.TIPO MODALIDAD",
        //         // "" => "DATOS DE POSTULACIÓN.TIPO ITINERARIO",
        //         // "" => "DATOS DE POSTULACIÓN.NOTA",
        //         // "" => "DATOS DE POSTULACIÓN.SITUACIÓN",
        //         // "" => "DATOS DE POSTULACIÓN.FECHA REGISTRO"
        //     ]),
        // ]);

        DB::table('import')->insert([
            'key' => 'registrations',
            'title' => 'REPORTE DE ESTUDIANTES MATRICULADOS',
            'attributes' => json_encode([
                "period" => "LECTIVO",
                "document_type" => "TIPO DOCUMENTO",
                "document_number" => "DOCUMENTO",
                "last_name_one" => "APELLIDO PATERNO",
                "last_name_two" => "APELLIDO MATERNO",
                "name" => "NOMBRES",
                "phone" => "CELULAR",
                "email" => "CORREO",
                "birth_date" => "FECHA NACIMIENTO ",
                "sex" => "SEXO",
                "native_language" => "LENGUA MATERNA",
                "name_program" => "PROGRAMA DE ESTUDIOS",
                "name_cycle" => "CICLO",
                "status_period" => "ESTADO PERIODO",
                "registration_status" => "ESTADO MATRICULA",
                "registration_date" => "FECHA  REGISTRO",
            ]),
        ]);

        DB::table('import')->insert([
            'key' => 'evaluations',
            'title' => 'REPORTE DE NOTAS',
            'attributes' => json_encode([
                "period" => "LECTIVO",
                "document_number" => "DOCUMENTO",
                "name_program" => "PROGRAMA DE ESTUDIOS",
                "name_section" => "SECCIÓN",
                "name_shift" => "TURNO",
                "name_cycle" => "PERIODO ACADÉMICO",
                "name_course" => "UNIDAD DIDÁCTICA",
                "score" => "NOTA",
            ]),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('import');
    }
};
