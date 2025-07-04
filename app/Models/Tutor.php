<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Log;

class Tutor extends Model
{
    use HasFactory, SoftDeletes;
    
    protected $table = 'tutores';
    
    protected $fillable = [
        'nombre',
        'apellido',
        'email',
        'telefono',
        'especialidad',
        'biografia',
        'activo'
    ];

    /**
     * Get the tesis for the tutor.
     */
    public function tesis()
    {
        return $this->hasMany(Tesis::class, 'tutor_id');
    }

    /**
     * Get the full name of the tutor.
     */
    public function getFullNameAttribute()
    {
        return "{$this->nombre} {$this->apellido}";
    }
    
    /**
     * Verify that changes were properly saved to the database.
     * 
     * @param array $attributes The attributes that should have been updated
     * @return bool
     */
    public function verifyUpdate(array $attributes)
    {
        // Refresh the model from the database
        $this->refresh();
        
        // Check if each attribute was properly updated
        foreach ($attributes as $key => $value) {
            if ($this->$key != $value) {
                \Log::warning("Tutor update verification failed for attribute {$key}: expected '{$value}', got '{$this->$key}'");
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Verify that a newly created tutor was properly saved to the database.
     * 
     * @param array $attributes The attributes that should have been saved
     * @return bool
     */
    public function verifyCreate(array $attributes)
    {
        // Refresh the model from the database to ensure we have the latest data
        $this->refresh();
        
        // Check if each attribute was properly saved
        foreach ($attributes as $key => $value) {
            if ($this->$key != $value) {
                \Log::warning("Tutor creation verification failed for attribute {$key}: expected '{$value}', got '{$this->$key}'");
                return false;
            }
        }
        
        \Log::info("Tutor creation verification passed for all attributes");
        return true;
    }
}
