<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class CalibrationReport extends Model
{
    protected $fillable = [
        'user_id',
        'laboratory_config_id',
        'equipment_id',
        'certificate_number',
        'issue_date',
        'calibration_date',
        'calibration_location'
    ];
    
    protected $casts = [
        'issue_date' => 'date',
        'calibration_date' => 'date'
    ];
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    public function laboratoryConfig()
    {
        return $this->belongsTo(LaboratoryConfig::class);
    }
    
    public function equipment()
    {
        return $this->belongsTo(Equipment::class);
    }
    
    // Scope para filtrar por usuario automáticamente
    protected static function booted()
    {
        static::addGlobalScope('userOwned', function (Builder $builder) {
            if (auth()->check()) {
                $builder->where('user_id', auth()->id());
            }
        });
    }
}