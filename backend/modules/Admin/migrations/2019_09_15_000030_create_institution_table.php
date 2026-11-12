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
        Schema::create('institution', function (Blueprint $table) {
            $table->id();
            $table->foreignId('domain_id')
                ->references('id')
                ->on('domain')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->string('modular_code');
            $table->string('ruc');
            $table->string('name');
            $table->string('type_management');
            $table->string('department');
            $table->string('province');
            $table->string('district');
            $table->string('address');
            $table->text('logo')->nullable();
            $table->boolean('is_active');
            $table->dateTime('start_date');
            $table->dateTime('end_date')->nullable();
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
        Schema::dropIfExists('institution');
    }
};
