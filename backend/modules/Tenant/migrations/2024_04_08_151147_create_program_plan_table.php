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
        Schema::create('program_plan', function (Blueprint $table) {
            $table->id();
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
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('program_plan');
    }
};
