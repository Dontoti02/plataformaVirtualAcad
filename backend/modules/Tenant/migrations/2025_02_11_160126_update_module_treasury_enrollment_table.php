<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {

      
        // add column discounts nullable in movement table
        Schema::table('treasury_movement', function (Blueprint $table) {
            $table->decimal('discounts', 10, 2)->nullable();
        });

        Schema::table('enrollment', function (Blueprint $table) {
          $table->dropForeign('enrollment_scale_id_foreign');
          $table->string('scale_authorization_document_type')->nullable()->change();
          $table->string('scale_authorization_document_number')->nullable()->change();
          $table->string('scale_authorization_full_names')->nullable()->change();
          $table->bigInteger('scale_id')->nullable()->change();
        });

        Schema::table('school_data', function (Blueprint $table) {
          $table->string('CEVA_certificate')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
  
    }
};
