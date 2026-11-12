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
        Schema::create('treasury_payment_concept', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name',255);
            
            $table->foreignId('treasury_denomination_id')
                ->references('id')
                ->on('treasury_denomination')
                ->onUpdate('cascade')
                ->onDelete('cascade');

            $table->decimal('gross_amount', 10, 2);
            $table->decimal('igv_amount', 10, 2);
            $table->decimal('net_amount', 10, 2);
            $table->integer('max_quotas')->default(1);
            $table->boolean('is_active')->default(true);
            $table->boolean('can_be_paid_in_quotas')->default(false);
            $table->boolean('include_in_enrollment')->default(false);
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->dateTime('deleted_at')->nullable();
        });

    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('treasury_payment_concept');
    }
};
