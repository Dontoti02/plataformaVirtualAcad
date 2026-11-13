<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('user_study_program')) {
            Schema::create('user_study_program', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')
                    ->references('id')
                    ->on('user')
                    ->onUpdate('cascade')
                    ->onDelete('cascade');
                $table->foreignId('study_program_id')
                    ->references('id')
                    ->on('study_program')
                    ->onUpdate('cascade')
                    ->onDelete('cascade');
                $table->dateTime('created_at')->nullable();
                $table->dateTime('updated_at')->nullable();

                $table->unique(['user_id', 'study_program_id'], 'user_study_program_unique');
            });
        }

        $userId = DB::table('user')
            ->join('person', 'user.person_id', '=', 'person.id')
            ->where('person.document_number', '16524979')
            ->value('user.id')
            ?? DB::table('user')->where('id', 581)->value('id');

        $studyProgramId = DB::table('study_program')
            ->where('name', 'CONTABILIDAD')
            ->value('id');

        if ($userId && $studyProgramId) {
            $exists = DB::table('user_study_program')
                ->where('user_id', $userId)
                ->where('study_program_id', $studyProgramId)
                ->exists();

            if (!$exists) {
                $now = Carbon::now();
                DB::table('user_study_program')->insert([
                    'user_id' => $userId,
                    'study_program_id' => $studyProgramId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('user_study_program');
    }
};
