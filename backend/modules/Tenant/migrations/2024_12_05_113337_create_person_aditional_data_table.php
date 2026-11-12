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
        Schema::create('person_aditional_data', function (Blueprint $table) {
            $table->id();
            $table->string('permanent_address', 255)->nullable();
            $table->string('current_address', 255)->nullable();
            $table->string('country', 100)->nullable();
            $table->string('department', 100)->nullable();
            $table->string('province', 100)->nullable();
            $table->string('district', 100)->nullable();
            $table->string('civil_status', 50)->nullable();
            $table->string('cell_phone', 20)->nullable();
            $table->foreignId('person_id')
            ->references('id')
            ->on('person')
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
        Schema::dropIfExists('person_aditional_data');
    }
};
