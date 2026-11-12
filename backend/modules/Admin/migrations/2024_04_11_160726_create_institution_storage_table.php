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
        Schema::create('institution_storage', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')
                ->references('id')
                ->on('institution')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->string('folder_name',250);
            $table->decimal('limit_space_mb',9,2);
            $table->decimal('used_space_mb',9,2);
            $table->decimal('free_space_mb',9,2);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('institution_storage');
    }
};
