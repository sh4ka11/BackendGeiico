<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Client extends Model
{
    use HasFactory;

    /**
     * Los atributos que son asignables masivamente.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'name',
        'address'
    ];

    // Scope global: filtra por el usuario autenticado en rutas NO admin
    protected static function booted()
    {
        static::addGlobalScope('userOwned', function (Builder $builder) {
            // Evitar afectar comandos (migraciones/seeders)
            if (app()->runningInConsole()) return;

            $user = auth()->user();

            // No aplicar en rutas admin; en el resto, filtrar por user_id
            if ($user && !request()->is('api/admin/*')) {
                $builder->where('user_id', $user->id);
            }
        });
    }

    /**
     * Obtener el usuario asociado con el cliente.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    /**
     * Scope para búsqueda en campos específicos
     */
    public function scopeSearch($query, $search)
    {
        if ($search) {
            return $query->where('name', 'LIKE', "%{$search}%")
                         ->orWhere('address', 'LIKE', "%{$search}%");
        }
        
        return $query;
    }
}
