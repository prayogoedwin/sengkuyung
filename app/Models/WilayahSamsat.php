<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WilayahSamsat extends Model
{
    use HasFactory;

    protected $table = 'wilayah_samsat'; // Nama tabel di database
    protected $primaryKey = 'id'; // Primary key menggunakan string
    public $incrementing = false; // Karena primary key bukan auto-increment
    protected $keyType = 'string'; // Primary key bertipe string

    protected $fillable = [
        'id',
        'nama',
        'kabkota',
    ];
}