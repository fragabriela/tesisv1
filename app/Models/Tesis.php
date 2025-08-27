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
        'documento_url',
        'github_repo',
        'project_type',
        'container_id',
        'container_status',
        'project_url',
        'project_config',
        'last_deployed',
        'is_visible',
        'project_repo_path'
    ];

    protected $dates = [
        'fecha_inicio',
        'fecha_fin',
        'created_at',
        'updated_at',
        'deleted_at',
    ];
    
    protected $casts = [
        'project_config' => 'array',
        'is_visible' => 'boolean',
        'last_deployed' => 'datetime',
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

    /**
     * Get the project backups for this tesis.
     */
    public function backups()
    {
        return $this->hasMany(ProjectBackup::class);
    }

    /**
     * Get the latest backup for this tesis.
     */
    public function latestBackup()
    {
        return $this->hasOne(ProjectBackup::class)->latest('backed_up_at');
    }

    /**
     * Get database backups only.
     */
    public function databaseBackups()
    {
        return $this->hasMany(ProjectBackup::class)->where('backup_type', 'database');
    }
}
