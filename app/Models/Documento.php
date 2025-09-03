<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Documento extends Model
{
    use HasFactory;

    protected $fillable = [
        'titulo',
        'descripcion',
        'archivo_original',
        'contenido_html',
        'contenido_texto',
        'tesis_id',
        'alumno_id',
        'tutor_id',
        'editado_por',
        'estado',
        'version',
        'fecha_ultima_edicion'
    ];

    protected $casts = [
        'fecha_ultima_edicion' => 'datetime',
    ];

    // Relaciones
    public function tesis()
    {
        return $this->belongsTo(Tesis::class, 'tesis_id');
    }

    public function alumno()
    {
        return $this->belongsTo(Alumno::class, 'alumno_id');
    }

    public function tutor()
    {
        return $this->belongsTo(Tutor::class, 'tutor_id');
    }

    public function usuarioCreacion()
    {
        return $this->belongsTo(User::class, 'editado_por');
    }

    public function usuarioUltimaModificacion()
    {
        return $this->belongsTo(User::class, 'editado_por');
    }

    public function comentarios()
    {
        return $this->hasMany(ComentarioDocumento::class, 'documento_id');
    }

    public function comentariosPendientes()
    {
        return $this->hasMany(ComentarioDocumento::class, 'documento_id')->where('estado', 'pendiente');
    }

    // Scopes
    public function scopePorEstado($query, $estado)
    {
        return $query->where('estado', $estado);
    }

    public function scopeRecientes($query)
    {
        return $query->orderBy('updated_at', 'desc');
    }
}
