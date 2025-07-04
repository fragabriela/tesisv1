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
        Schema::create('tesis', function (Blueprint $table) {
            $table->id();
            $table->string('titulo');
            $table->text('descripcion');
            $table->date('fecha_inicio');
            $table->date('fecha_fin')->nullable();
            $table->foreignId('alumno_id')->constrained('alumnos');
            $table->foreignId('tutor_id')->constrained('tutores');
            $table->enum('estado', ['pendiente', 'en_progreso', 'completado', 'rechazado'])->default('pendiente');
            $table->integer('calificacion')->nullable();
            $table->text('observaciones')->nullable();
            $table->string('documento_url')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tesis');
    }
};
