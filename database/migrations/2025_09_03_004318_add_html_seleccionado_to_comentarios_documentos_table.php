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
        Schema::table('comentarios_documentos', function (Blueprint $table) {
            $table->text('html_seleccionado')->nullable()->after('texto_seleccionado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('comentarios_documentos', function (Blueprint $table) {
            $table->dropColumn('html_seleccionado');
        });
    }
};
