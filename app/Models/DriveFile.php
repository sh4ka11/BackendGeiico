<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DriveFile extends Model
{
    protected $fillable = [
        'user_id', 'drive_file_id', 'name', 'mime_type', 'parent_id'
    ];
}
