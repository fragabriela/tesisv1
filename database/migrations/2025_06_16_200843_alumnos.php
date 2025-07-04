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
          Schema::create('alumnos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('apellido');
            $table->string('email')->unique();
            $table->string('telefono');
            $table->string('cedula')->unique();
            $table->string('matricula')->unique();
            $table->date('fecha_nacimiento');
            $table->foreignId('id_carrera')->constrained('carreras');
            $table->enum('estado', ['activo', 'inactivo', 'egresado'])->default('activo');
            $table->timestamps();
            $table->softDeletes(); // Add soft delete capability
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alumnos');
    }
};
