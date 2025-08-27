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
        Schema::create('project_backups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tesis_id')->constrained('tesis')->onDelete('cascade');
            $table->string('backup_name');
            $table->string('backup_type'); // 'database', 'files', 'full'
            $table->string('file_path');
            $table->string('file_name');
            $table->bigInteger('file_size')->default(0);
            $table->json('metadata')->nullable(); // configuraciones específicas
            $table->text('description')->nullable();
            $table->boolean('is_automatic')->default(false);
            $table->timestamp('backed_up_at');
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['tesis_id', 'backup_type']);
            $table->index('backed_up_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_backups');
    }
};
