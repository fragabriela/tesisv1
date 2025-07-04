<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Alumno extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'alumnos';
    
    protected $fillable = [
        'nombre',
        'apellido',
        'email',
        'telefono',
        'cedula',
        'matricula',
        'fecha_nacimiento',
        'direccion',
        'id_carrera',
        'estado'
    ];
    
    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'fecha_nacimiento' => 'date',
    ];
    
    /**
     * Override the save method to ensure it works correctly
     * and logs any potential issues
     *
     * @param array $options
     * @return bool
     */
    public function save(array $options = [])
    {
        try {
            // Log what we're about to save
            \Log::info('Alumno saving: ID=' . ($this->id ?? 'new') . ', Changes=' . json_encode($this->getDirty()));
            
            // Call the parent save method
            $result = parent::save($options);
            
            // Log the result
            \Log::info('Alumno save result: ' . ($result ? 'success' : 'failed'));
            
            return $result;
        } catch (\Exception $e) {
            \Log::error('Error in Alumno save: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get the carrera that owns the alumno.
     */
    public function carrera()
    {
        return $this->belongsTo(Carrera::class, 'id_carrera');
    }

    /**
     * Get the tesis for the alumno.
     */
    public function tesis()
    {
        return $this->hasMany(Tesis::class, 'alumno_id');
    }

    /**
     * Get the full name of the alumno.
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
                \Log::warning("Alumno update verification failed for attribute {$key}: expected '{$value}', got '{$this->$key}'");
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Verify that a newly created alumno was properly saved to the database.
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
                \Log::warning("Alumno creation verification failed for attribute {$key}: expected '{$value}', got '{$this->$key}'");
                return false;
            }
        }
        
        \Log::info("Alumno creation verification passed for all attributes");
        return true;
    }
}
