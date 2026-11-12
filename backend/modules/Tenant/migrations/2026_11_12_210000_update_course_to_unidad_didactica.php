<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('option')
            ->where('id', 2)
            ->update(['name' => 'Mis unidades didácticas']);

        DB::table('content_group')
            ->where('title', 'Sílabo')
            ->update(['title' => 'Documentos Pedagógicos']);
    }

    public function down(): void
    {
        DB::table('option')
            ->where('id', 2)
            ->update(['name' => 'Mis cursos']);

        DB::table('content_group')
            ->where('title', 'Documentos Pedagógicos')
            ->update(['title' => 'Sílabo']);
    }
};
