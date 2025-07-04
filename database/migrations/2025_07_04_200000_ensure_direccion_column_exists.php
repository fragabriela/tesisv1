<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First check if the column already exists
        if (!Schema::hasColumn('alumnos', 'direccion')) {
            Schema::table('alumnos', function (Blueprint $table) {
                $table->string('direccion')->nullable()->after('fecha_nacimiento');
            });
            DB::statement('UPDATE alumnos SET direccion = NULL');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Only drop the column if it exists
        if (Schema::hasColumn('alumnos', 'direccion')) {
            Schema::table('alumnos', function (Blueprint $table) {
                $table->dropColumn('direccion');
            });
        }
    }
};
