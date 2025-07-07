<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SengSaamsat extends Model
{
    use HasFactory;

    protected $table = 'seng_samsat'; // Nama tabel di database
    protected $primaryKey = 'id'; // Primary key menggunakan string
    public $incrementing = false; // Karena primary key bukan auto-increment
    protected $keyType = 'string'; // Primary key bertipe string

    protected $fillable = [
        'id',
        'lokasi',
        'id_wilayah_samsat',
        'kabkota',
        'lokasi_singkat',
        'alamat',
        'telp',
        'fax',
        'lat',
        'lng',
        'created_at',
        'updated_at'
    ];
}
