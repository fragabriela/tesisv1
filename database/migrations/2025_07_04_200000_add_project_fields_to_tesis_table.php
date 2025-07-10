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
            $table->string('github_repo')->nullable()->after('documento_url');
            $table->string('project_type')->nullable()->after('github_repo'); // laravel, java, etc.
            $table->string('container_id')->nullable()->after('project_type');
            $table->string('container_status')->nullable()->after('container_id');
            $table->string('project_url')->nullable()->after('container_status');
            $table->json('project_config')->nullable()->after('project_url');
            $table->timestamp('last_deployed')->nullable()->after('project_config');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tesis', function (Blueprint $table) {
            $table->dropColumn([
                'github_repo',
                'project_type',
                'container_id',
                'container_status',
                'project_url',
                'project_config',
                'last_deployed',
            ]);
        });
    }
};
