<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectBackup extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tesis_id',
        'backup_name',
        'backup_type',
        'file_path',
        'file_name',
        'file_size',
        'metadata',
        'description',
        'is_automatic',
        'backed_up_at',
        'is_temporary',
    ];

    protected $casts = [
        'metadata' => 'array',
        'is_automatic' => 'boolean',
        'is_temporary' => 'boolean',
        'backed_up_at' => 'datetime',
        'file_size' => 'integer',
    ];

    protected $dates = [
        'backed_up_at',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    /**
     * Relación con Tesis
     */
    public function tesis(): BelongsTo
    {
        return $this->belongsTo(Tesis::class);
    }

    /**
     * Obtener el tamaño formateado del archivo
     */
    public function getFormattedSizeAttribute(): string
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Obtener la ruta completa del archivo
     */
    public function getFullPathAttribute(): string
    {
        return storage_path('app/' . $this->file_path . '/' . $this->file_name);
    }

    /**
     * Verificar si el archivo existe
     */
    public function fileExists(): bool
    {
        return file_exists($this->full_path);
    }

    /**
     * Scopes
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('backup_type', $type);
    }

    public function scopeAutomatic($query)
    {
        return $query->where('is_automatic', true);
    }

    public function scopeManual($query)
    {
        return $query->where('is_automatic', false);
    }

    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('backed_up_at', '>=', now()->subDays($days));
    }
}
