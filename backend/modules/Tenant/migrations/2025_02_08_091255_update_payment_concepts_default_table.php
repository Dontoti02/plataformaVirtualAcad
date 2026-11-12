<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
      $date = Carbon::now();

      $treasury_denomination_matricula_id = DB::table('treasury_denomination')->where('id', '1')->first()->id;
      $treasury_denomination_pension_1 = DB::table('treasury_denomination')->where('id', '2')->first()->id;

      $paymentConceptMatricula = DB::table('treasury_payment_concept')->where('code', 'PC-0001')->first();
      $paymentConceptPensiones = DB::table('treasury_payment_concept')->where('code', 'PC-0002')->first();

      if ($paymentConceptMatricula) {
          DB::table('treasury_payment_concept')->where('code', 'PC-0001')->update([
          'name' => 'MATRICULA',
          'treasury_denomination_id' => $treasury_denomination_matricula_id,
          'gross_amount' => 100,
          'igv_amount' => 18,
          'net_amount' => 118,
          'max_quotas' => 1,
          'can_be_paid_in_quotas' => false,
          'include_in_enrollment' => true,
          'is_active' => true,
          'updated_at' => $date,
          ]);
      } else {
          DB::table('treasury_payment_concept')->insert([
          'code' => 'PC-0001',
          'name' => 'MATRICULA',
          'treasury_denomination_id' => $treasury_denomination_matricula_id,
          'gross_amount' => 100,
          'igv_amount' => 18,
          'net_amount' => 118,
          'max_quotas' => 1,
          'can_be_paid_in_quotas' => false,
          'include_in_enrollment' => true,
          'is_active' => true,
          'created_at' => $date,
          'updated_at' => $date,
          ]);
      }

      if ($paymentConceptPensiones) {
          DB::table('treasury_payment_concept')->where('code', 'PC-0002')->update([
          'name' => 'PENSIONES',
          'treasury_denomination_id' => $treasury_denomination_pension_1,
          'gross_amount' => 100,
          'igv_amount' => 18,
          'net_amount' => 118,
          'max_quotas' => 1,
          'can_be_paid_in_quotas' => false,
          'include_in_enrollment' => true,
          'is_active' => true,
          'updated_at' => $date,
          ]);
      } else {
          DB::table('treasury_payment_concept')->insert([
          'code' => 'PC-0002',
          'name' => 'PENSIONES',
          'treasury_denomination_id' => $treasury_denomination_pension_1,
          'gross_amount' => 100,
          'igv_amount' => 18,
          'net_amount' => 118,
          'max_quotas' => 1,
          'can_be_paid_in_quotas' => false,
          'include_in_enrollment' => true,
          'is_active' => true,
          'created_at' => $date,
          'updated_at' => $date,
          ]);
      }

      // CERTIFICATE BACKGROUND CREATE OR UPDATED
      $certificateBackground = DB::table('system_configuration')->where('key', 'certificate_background')->first();

      if ($certificateBackground) {
        DB::table('system_configuration')->where('key', 'certificate_background')->update([
            'value' => 'default/certificate_background.png',
            'updated_at' => $date,
        ]);
      } else {
        DB::table('system_configuration')->insert([
            'key' => 'certificate_background',
            'name' => 'Fondo del certificado',
            'type' => 'string',
            'value' => 'default/certificate_background.png',
            'created_at' => $date,
        ]);
      }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
