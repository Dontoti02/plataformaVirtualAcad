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

        DB::table('system_configuration')->insert([
          [
              'key' => 'certificate_title',
              'name' => 'Titulo del certificado',
              'type' => 'string',
              'value' => 'CERTIFICADO DE GRADUACIÓN',
              'created_at' => $date,
          ],
          [
              'key' => 'certificate_director',
              'name' => 'Director del certificado',
              'type' => 'string',
              'value' => 'Dr. John Doe',
              'created_at' => $date,
          ],
          [
              'key' => 'certificate_secretary',
              'name' => 'Secretario del certificado',
              'type' => 'string',
              'value' => 'Lic. Jane Doe',
              'created_at' => $date,
          ],
          [
              'key' => 'certificate_address',
              'name' => 'Dirección del certificado',
              'type' => 'string',
              'value' => 'Calle Cualquiera 123, Cualquier Lugar',
              'created_at' => $date,
          ]
        ]);

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {}
};
