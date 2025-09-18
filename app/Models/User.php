<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    /**
     * Get the alumno associated with the user.
     */
    public function alumno()
    {
        return $this->hasOne(Alumno::class);
    }

    /**
     * Get the tutor associated with the user.
     */
    public function tutor()
    {
        return $this->hasOne(Tutor::class);
    }

    /**
     * Check if user is an alumno
     */
    public function isAlumno()
    {
        return $this->hasRole('alumno');
    }

    /**
     * Check if user is a tutor
     */
    public function isTutor()
    {
        return $this->hasRole('tutor');
    }

    /**
     * Check if user is an admin or coordinador
     */
    public function isAdminOrCoordinador()
    {
        return $this->hasRole(['administrador', 'coordinador']);
    }
}
