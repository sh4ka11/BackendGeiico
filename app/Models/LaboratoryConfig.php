<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

class LaboratoryConfig extends Model
{
    use SoftDeletes;
    
    protected $fillable = [
        'user_id', 
        'lab_name', 
        'onac_number', 
        'document_title'
    ];
    
    /**
     * The "booted" method of the model.
     * Agrega un scope global para filtrar por el usuario autenticado
     */
    protected static function booted()
    {
        static::addGlobalScope('user', function (Builder $builder) {
            if (auth()->check()) {
                $builder->where('user_id', auth()->id());
            }
        });
    }
    
    public function calibrationReports()
    {
        return $this->hasMany(CalibrationReport::class);
    }
}
