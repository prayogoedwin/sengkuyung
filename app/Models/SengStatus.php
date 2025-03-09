<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SengStatus extends Model
{
    use HasFactory;

    protected $table = 'seng_status';

    protected $fillable = [
        'nama',
        'keterangan',
    ];

    public function files()
    {
        return $this->hasMany(SengStatusFile::class, 'id_status');
    }
}
