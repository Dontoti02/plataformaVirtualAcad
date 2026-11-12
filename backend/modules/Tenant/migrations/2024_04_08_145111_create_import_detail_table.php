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
        Schema::create('import_detail', function (Blueprint $table) {
            $table->id();
            $table->foreignId('import_id')
                ->references('id')
                ->on('import')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->boolean('is_current');
            $table->boolean('is_active');
            $table->string('status');
            $table->integer('progress');
            $table->dateTime('date');
            $table->integer('time_elapsed');
            $table->longText('log');
            $table->json('summary')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('import_detail');
    }
};
