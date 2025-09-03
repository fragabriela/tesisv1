<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ComentarioDocumento extends Model
{
    use HasFactory;

    protected $table = 'comentarios_documentos';

    protected $fillable = [
        'documento_id',
        'usuario_id',
        'comentario',
        'posicion_inicio',
        'posicion_fin',
        'texto_seleccionado',
        'html_seleccionado',
        'tipo',
        'estado',
        'respondido_por',
        'respuesta',
        'fecha_respuesta'
    ];

    protected $casts = [
        'fecha_respuesta' => 'datetime',
    ];

    // Relaciones
    public function documento()
    {
        return $this->belongsTo(Documento::class);
    }

    public function usuario()
    {
        return $this->belongsTo(User::class);
    }

    public function respondidoPor()
    {
        return $this->belongsTo(User::class, 'respondido_por');
    }

    // Scopes
    public function scopePendientes($query)
    {
        return $query->where('estado', 'pendiente');
    }

    public function scopeResueltos($query)
    {
        return $query->where('estado', 'resuelto');
    }

    public function scopePorTipo($query, $tipo)
    {
        return $query->where('tipo', $tipo);
    }
}
