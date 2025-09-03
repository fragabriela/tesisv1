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
        Schema::create('comentarios_documentos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('documento_id');
            $table->unsignedBigInteger('usuario_id');
            $table->text('comentario');
            $table->integer('posicion_inicio')->nullable(); // Posición del texto seleccionado
            $table->integer('posicion_fin')->nullable();
            $table->string('texto_seleccionado')->nullable(); // Fragmento de texto comentado
            $table->enum('tipo', ['revision', 'sugerencia', 'aprobacion', 'corrección'])->default('revision');
            $table->enum('estado', ['pendiente', 'resuelto', 'descartado'])->default('pendiente');
            $table->unsignedBigInteger('respondido_por')->nullable(); // Usuario que respondió
            $table->text('respuesta')->nullable(); // Respuesta del alumno
            $table->timestamp('fecha_respuesta')->nullable();
            $table->timestamps();
            
            // Foreign keys
            $table->foreign('documento_id')->references('id')->on('documentos')->onDelete('cascade');
            $table->foreign('usuario_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('respondido_por')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comentarios_documentos');
    }
};
