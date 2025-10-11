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
        'project_repo_path',
        'backup_restored',
        'env_configured',
        'backup_restored_at'
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
        'project_config' => 'array',
        'is_visible' => 'boolean',
        'backup_restored' => 'boolean',
        'env_configured' => 'boolean',
        'last_deployed' => 'datetime',
        'backup_restored_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
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
     * Get the documentos associated with this tesis.
     */
    public function documentos()
    {
        return $this->hasMany(Documento::class, 'tesis_id');
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
    
    /**
     * Check if the project is ready for deployment
     */
    public function isReadyForDeployment()
    {
        // Con el nuevo sistema, solo necesitamos que el repositorio esté clonado
        // El deployment automáticamente creará la BD, configurará .env y ejecutará migraciones
        return !empty($this->project_repo_path);
    }
    
    /**
     * Get deployment readiness status with details
     */
    public function getDeploymentReadinessStatus()
    {
        return [
            'repository_cloned' => !empty($this->project_repo_path),
            'backup_restored' => $this->backup_restored,
            'env_configured' => $this->env_configured,
            'ready_for_deployment' => $this->isReadyForDeployment()
        ];
    }
}
