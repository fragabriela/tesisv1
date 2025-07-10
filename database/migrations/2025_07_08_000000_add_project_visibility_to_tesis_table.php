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
            if (!Schema::hasColumn('tesis', 'github_repo')) {
                $table->string('github_repo')->nullable();
            }
            
            if (!Schema::hasColumn('tesis', 'project_repo_path')) {
                $table->string('project_repo_path')->nullable();
            }
            
            if (!Schema::hasColumn('tesis', 'project_type')) {
                $table->string('project_type')->nullable();
            }
            
            if (!Schema::hasColumn('tesis', 'container_id')) {
                $table->string('container_id')->nullable();
            }
            
            if (!Schema::hasColumn('tesis', 'container_status')) {
                $table->string('container_status')->nullable();
            }
            
            if (!Schema::hasColumn('tesis', 'project_url')) {
                $table->string('project_url')->nullable();
            }
            
            if (!Schema::hasColumn('tesis', 'project_config')) {
                $table->json('project_config')->nullable();
            }
            
            if (!Schema::hasColumn('tesis', 'last_deployed')) {
                $table->timestamp('last_deployed')->nullable();
            }

            if (!Schema::hasColumn('tesis', 'is_visible')) {
                $table->boolean('is_visible')->default(true);
            }
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
                'project_repo_path', 
                'project_type', 
                'container_id',
                'container_status',
                'project_url',
                'project_config',
                'last_deployed',
                'is_visible'
            ]);
        });
    }
};
