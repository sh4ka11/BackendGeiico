<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Permission extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug', 'description'];
    
    /**
     * Relación con roles
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class);
    }

    /**
     * Obtener usuarios que tienen este permiso a través de sus roles
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function users()
    {
        return $this->hasManyThrough(
            User::class,
            Role::class,
            'permission_id', // Clave foránea en la tabla pivot permission_role
            'id', // Clave local en la tabla users
            'id', // Clave local en permissions
            'user_id' // Clave foránea en la tabla pivot role_user
        );
    }
    
    /**
     * Scope para buscar permisos por su slug
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $slug
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFindBySlug($query, $slug)
    {
        return $query->where('slug', $slug);
    }
    
    /**
     * Verifica si el permiso está asignado a un rol específico
     * 
     * @param string|int $roleId Identificador del rol
     * @return bool
     */
    public function isAssignedToRole($roleId)
    {
        return $this->roles()->where('roles.id', $roleId)->exists();
    }

    /**
     * Obtener lista de roles que tienen este permiso
     * 
     * @return array
     */
    public function getRolesList()
    {
        return $this->roles->pluck('name', 'id')->toArray();
    }

    /**
     * Boot the model
     * Asegurar que el slug sea único antes de guardar
     */
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($permission) {
            // Asegurar que el slug sea único
            if (static::where('slug', $permission->slug)->exists()) {
                $permission->slug = $permission->slug . '-' . uniqid();
            }
        });
    }
}
