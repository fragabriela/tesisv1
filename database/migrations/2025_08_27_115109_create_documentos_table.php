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
        Schema::create('documentos', function (Blueprint $table) {
            $table->id();
            $table->string('titulo');
            $table->text('descripcion')->nullable();
            $table->string('archivo_original')->nullable(); // Archivo Word original
            $table->longText('contenido_html')->nullable(); // Contenido convertido a HTML
            $table->longText('contenido_texto')->nullable(); // Contenido en texto plano
            $table->unsignedBigInteger('tesis_id');
            $table->unsignedBigInteger('alumno_id');
            $table->unsignedBigInteger('tutor_id');
            $table->enum('estado', ['borrador', 'revision', 'aprobado', 'corregir'])->default('borrador');
            $table->integer('version')->default(1);
            $table->timestamp('fecha_ultima_edicion')->nullable();
            $table->unsignedBigInteger('editado_por')->nullable();
            $table->timestamps();
            
            // Foreign keys
            $table->foreign('tesis_id')->references('id')->on('tesis')->onDelete('cascade');
            $table->foreign('alumno_id')->references('id')->on('alumnos')->onDelete('cascade');
            $table->foreign('tutor_id')->references('id')->on('tutores')->onDelete('cascade');
            $table->foreign('editado_por')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documentos');
    }
};
