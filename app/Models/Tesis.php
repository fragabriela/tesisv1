<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tesis extends Model
{
    use HasFactory, SoftDeletes;
    
    protected $table = 'tesis';
    
    protected $fillable = [
        'titulo',
        'descripcion',
        'fecha_inicio',
        'fecha_fin',
        'alumno_id',
        'tutor_id',
        'estado',
        'calificacion',
        'observaciones',
        'documento_url'
    ];

    protected $dates = [
        'fecha_inicio',
        'fecha_fin',
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    /**
     * Get the alumno that owns the tesis.
     */
    public function alumno()
    {
        return $this->belongsTo(Alumno::class, 'alumno_id');
    }

    /**
     * Get the tutor that supervises the tesis.
     */
    public function tutor()
    {
        return $this->belongsTo(Tutor::class, 'tutor_id');
    }
}
