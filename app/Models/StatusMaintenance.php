<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StatusMaintenance extends Model
{
    use HasFactory;

    protected $table = 'status_maintenance';

    protected $fillable = [
        'maintenance',
        'updated_by',
    ];

    protected $casts = [
        'maintenance' => 'boolean',
    ];
}
