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
            $table->boolean('backup_restored')->default(false)->after('project_repo_path');
            $table->boolean('env_configured')->default(false)->after('backup_restored');
            $table->timestamp('backup_restored_at')->nullable()->after('env_configured');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tesis', function (Blueprint $table) {
            $table->dropColumn(['backup_restored', 'env_configured', 'backup_restored_at']);
        });
    }
};
