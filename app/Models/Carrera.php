<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Carrera extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'carreras';
    
    protected $fillable = [
        'nombre',
        'descripcion',
        'activo'
    ];

    /**
     * Get the alumnos for the carrera.
     */
    public function alumnos()
    {
        return $this->hasMany(Alumno::class, 'id_carrera');
    }
}
