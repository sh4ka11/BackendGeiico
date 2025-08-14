<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Facades\Log;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'google_id',
        'avatar'
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
    ];

    /**
     * Get the drive files for the user.
     */
    public function driveFiles()
    {
        return $this->hasMany(DriveFile::class);
    }
    
    /**
     * Relación con roles
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class);
    }
    
    /**
     * Verifica si el usuario tiene un rol específico
     * @param string $role Slug del rol a verificar
     * @return bool
     */
    public function hasRole($role)
    {
        // Cargar la relación si no está cargada
        if (!$this->relationLoaded('roles')) {
            $this->load('roles');
        }
        
        // Verificar si el usuario tiene el rol especificado
        return $this->roles->contains('slug', $role);
    }

    /**
     * Verifica si el usuario es administrador
     * @return bool
     */
    public function isAdmin()
    {
        // Cargar la relación si no está cargada
        if (!$this->relationLoaded('roles')) {
            $this->load('roles');
        }
        
        return $this->roles->contains('slug', 'admin');
    }

    /**
     * Verifica si el usuario tiene un permiso específico
     * @param string $permission Slug del permiso a verificar
     * @return bool
     */
    public function hasPermission($permission)
    {
        try {
            // Si el usuario es administrador, tiene todos los permisos
            if ($this->isAdmin()) {
                return true;
            }
            
            // Cargar roles con sus permisos si no están cargados
            if (!$this->relationLoaded('roles')) {
                $this->load('roles.permissions');
            }
            
            // Verificar en cada rol si tiene el permiso específico
            foreach ($this->roles as $role) {
                foreach ($role->permissions as $perm) {
                    if ($perm->slug === $permission) {
                        return true;
                    }
                }
            }
            
            return false;
        } catch (\Exception $e) {
            Log::error('Error en hasPermission: ' . $e->getMessage());
            return false; // Por defecto, denegar acceso si hay error
        }
    }
}
