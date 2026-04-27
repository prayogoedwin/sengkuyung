<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DataTertagih extends Model
{
    use HasFactory;

    protected $table = 'data_tertagih';

    protected $fillable = [
        'no_polisi',
        'id_lokasi_samsat',
        'lokasi_layanan',
        'id_kecamatan',
        'nm_kecamatan',
        'id_kelurahan',
        'nm_kelurahan',
        'alamat',
        'is_terdata',
        'year',
        'created_at',
        'created_by',
        'updated_at',
        'updated_by',
    ];

    protected $casts = [
        'is_terdata' => 'integer',
        'year' => 'integer',
        'created_by' => 'integer',
        'updated_by' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
