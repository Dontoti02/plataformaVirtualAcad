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
        Schema::create('school_data', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('modular_code', 50);
            $table->string('name', 255);
            $table->date('start_date');
            $table->integer('end_date');
            $table->string('type', 50);
            $table->string('category', 50);
            $table->string('CEVA_certificate', 255);
            $table->string('condition', 50);
            $table->text('observations');
            $table->string('photo', 255);
            $table->string('academic_validation', 255);
            $table->foreignId('person_id')
            ->references('id')
            ->on('person')
            ->onUpdate('cascade')
            ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('school_data');
    }
};
