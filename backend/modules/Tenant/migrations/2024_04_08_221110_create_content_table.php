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
        Schema::create('content', function (Blueprint $table) {
            $table->id();
            $table->foreignId('content_group_id')
                ->references('id')
                ->on('content_group')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->foreignId('evaluation_group_id')
                ->nullable()
                ->references('id')
                ->on('evaluation_group')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->string('title');
            $table->longText('description')->nullable();
            $table->string('type');
            $table->boolean('is_visible');
            $table->dateTime('date_start')->nullable();
            $table->dateTime('date_limit')->nullable();
            $table->boolean('is_open');
            $table->decimal('score', 4, 2)->nullable();
            $table->boolean('has_evaluation_form');
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
        Schema::dropIfExists('content');
    }
};
