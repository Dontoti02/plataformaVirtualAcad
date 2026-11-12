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
        Schema::create('average_detail', function (Blueprint $table) {
            $table->id();
            $table->foreignId('person_id')
                ->references('id')
                ->on('person')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->foreignId('content_group_id')
                ->references('id')
                ->on('content_group')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->foreignId('evaluation_group_id')
                ->references('id')
                ->on('evaluation_group')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->decimal('score', 4, 2)->default(0.00);
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
        Schema::dropIfExists('average_detail');
    }
};
