<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SengWilayah extends Model
{
    use HasFactory;

    protected $table = 'seng_wilayah';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'kode',
        'nama',
        'id_up',
        'lat',
        'lng'
    ];

    public function kecamatan()
    {
        return $this->hasMany(SengWilayah::class, 'kec', 'id');
    }
}
