<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('question_type', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('name', 255);
            $table->string('data_type', 100);
            $table->integer('order_number');
            $table->json('options')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->dateTime('deleted_at')->nullable();
        });

        $date = Carbon::now();

        DB::table('question_type')->insert([
            'id' => 1,
            'key' => 'OPTION',
            'name' => 'CASILLA DE VERIFICACIÓN',
            'data_type' => 'option',
            'order_number' => 1,
            'created_at' => $date,
        ]);
        DB::table('question_type')->insert([
            'id' => 2,
            'key' => 'OPTION_MULTIPLE',
            'name' => 'SELECCIÓN MÚLTIPLE',
            'data_type' => 'option_multiple',
            'order_number' => 2,
            'created_at' => $date,
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('question_type');
    }
};
