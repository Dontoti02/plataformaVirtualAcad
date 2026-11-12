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
        Schema::create('evaluation_group', function (Blueprint $table) {
            $table->id();
            $table->foreignId('classroom_id')
                ->references('id')
                ->on('classroom')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->string('title');
            $table->decimal('weight', 3, 2);
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
        Schema::dropIfExists('evaluation_group');
    }
};
