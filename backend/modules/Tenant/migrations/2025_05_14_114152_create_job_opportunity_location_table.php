<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('job_opportunity_location', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->dateTime('deleted_at')->nullable();
        });

        DB::table('job_opportunity_location')->insert([
            ['name' => 'Remoto', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Presencial', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Híbrido', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_opportunity_location');
    }
};
