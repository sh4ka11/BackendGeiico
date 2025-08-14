<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Equipment extends Model
{
    // Añade esta línea para especificar explícitamente el nombre de la tabla
    protected $table = 'equipments';
    
    protected $fillable = [
        'user_id',
        'client_id', 
        'equipment_type', 
        'brand_model', 
        'serial_number', 
        'internal_code',
        'is_bidirectional'
    ];
    
    protected $casts = [
        'is_bidirectional' => 'boolean',
    ];
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    public function client()
    {
        return $this->belongsTo(Client::class);
    }
    
    // Scope para filtrar por usuario automáticamente
    protected static function booted()
    {
        static::addGlobalScope('userOwned', function ($builder) {
            if (auth()->check()) {
                $builder->where('user_id', auth()->id());
            }
        });
    }
}
