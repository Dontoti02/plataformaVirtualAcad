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
        Schema::create('treasury_movement', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();

            $table->foreignId('treasury_movement_type_id')
                ->references('id')
                ->on('treasury_movement_type')
                ->onUpdate('cascade')
                ->onDelete('cascade');

            $table->foreignId('treasury_payment_concept_id')
                ->references('id')
                ->on('treasury_payment_concept')
                ->onUpdate('cascade')
                ->onDelete('cascade');

            $table->foreignId('period_id')
                ->references('id')
                ->on('period')
                ->onUpdate('cascade')
                ->onDelete('cascade');

            $table->foreignId('person_id')
                ->references('id')
                ->on('person')
                ->onUpdate('cascade')
                ->onDelete('cascade');

            $table->foreignId('person_registration_id')
                ->references('id')
                ->on('person')
                ->onUpdate('cascade')
                ->onDelete('cascade');

            $table->decimal('amount', 10, 2);
            $table->decimal('initial_amount', 10, 2);
            $table->decimal('amount_to_divide', 10, 2);
            $table->integer('quotas')->default(1);
            $table->boolean('is_paid')->default(false);
            $table->decimal('remaining_amount', 10, 2);
            $table->dateTime('due_date');
            $table->dateTime('payment_date')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->dateTime('deleted_at')->nullable();
            $table->bigInteger('refund_movement_id')->nullable();
            $table->boolean('is_exonerated')->default(false);
            $table->string('exoneration_reason')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('treasury_movement');
    }
};
