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
        Schema::create('tutor_alumno', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tutor_id');
            $table->unsignedBigInteger('alumno_id');
            $table->timestamps();

            // Foreign keys
            $table->foreign('tutor_id')->references('id')->on('tutores')->onDelete('cascade');
            $table->foreign('alumno_id')->references('id')->on('alumnos')->onDelete('cascade');

            // Unique constraint to prevent duplicate associations
            $table->unique(['tutor_id', 'alumno_id'], 'tutor_alumno_unique');
            
            // Indexes for better performance
            $table->index('tutor_id');
            $table->index('alumno_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tutor_alumno');
    }
};
