<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DriveFileBackup extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'drive_file_id', 'name', 'mime_type', 'parent_id', 'deleted_at'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];
}
