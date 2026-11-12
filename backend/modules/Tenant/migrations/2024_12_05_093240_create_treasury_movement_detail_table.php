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
        Schema::create('treasury_movement_detail', function (Blueprint $table) {
            $table->id();

            $table->foreignId('treasury_movement_id')
                ->references('id')
                ->on('treasury_movement')
                ->onUpdate('cascade')
                ->onDelete('cascade');

            $table->foreignId('treasury_payment_concept_id')
                ->references('id')
                ->on('treasury_payment_concept')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            
            // person registration payment
            $table->foreignId('person_registration_payment_id')
                ->references('id')
                ->on('person')
                ->onUpdate('cascade')
                ->onDelete('cascade');

            // person generate schedule
            $table->foreignId('person_created_schedule_by')
            ->references('id')
                ->on('person')
                ->onUpdate('cascade')
                ->onDelete('cascade');

            $table->decimal('amount', 10, 2);
            $table->boolean('is_paid')->default(false);
            $table->string('movement_voucher')->nullable();
            $table->dateTime('due_date');
            $table->dateTime('emission_date');
            $table->dateTime('payment_date')->nullable();
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
        Schema::dropIfExists('treasury_movement_detail');
    }
};
