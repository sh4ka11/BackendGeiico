<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserDefaultValue extends Model
{
    protected $fillable = ['user_id', 'field_name', 'field_value'];
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
