<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SengStatusVerifikasi extends Model
{
    use HasFactory;

    protected $table = 'seng_status_verifikasi';

    protected $fillable = [
        'nama',
        'keterangan',
    ];
}
