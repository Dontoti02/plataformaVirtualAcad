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
        Schema::create('enrollment', function (Blueprint $table) {
            $table->id();
            $table->date('registration_date');
            $table->string('scale_authorization_document_type', 50);
            $table->string('scale_authorization_document_number', 50);
            $table->string('scale_authorization_full_names', 255);
            $table->text('observations')->nullable();
            $table->boolean('is_full_payment');
            $table->foreignId('person_id')
            ->references('id')
            ->on('person')
            ->onUpdate('cascade')
            ->onDelete('cascade');
            $table->foreignId('period_id')
            ->references('id')
            ->on('period')
            ->onUpdate('cascade')
            ->onDelete('cascade');
            $table->foreignId('study_program_id')
            ->references('id')
            ->on('study_program')
            ->onUpdate('cascade')
            ->onDelete('cascade');
            $table->foreignId('study_plan_id')
            ->references('id')
            ->on('study_plan')
            ->onUpdate('cascade')
            ->onDelete('cascade');
            $table->foreignId('cycle_id')
            ->references('id')
            ->on('cycle')
            ->onUpdate('cascade')
            ->onDelete('cascade');
            $table->foreignId('shift_id')
            ->references('id')
            ->on('shift')
            ->onUpdate('cascade')
            ->onDelete('cascade');
            $table->foreignId('section_id')
            ->references('id')
            ->on('section')
            ->onUpdate('cascade')
            ->onDelete('cascade');
            $table->foreignId('scale_id')
            ->nullable()
            ->references('id')
            ->on('scale')
            ->onUpdate('cascade')
            ->onDelete('cascade');  
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('enrollment');
    }
};