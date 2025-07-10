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
        Schema::table('tesis', function (Blueprint $table) {
            $table->text('deployment_error')->nullable()->after('container_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tesis', function (Blueprint $table) {
            $table->dropColumn('deployment_error');
        });
    }
};
