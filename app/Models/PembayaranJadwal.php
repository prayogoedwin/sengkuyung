<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PembayaranJadwal extends Model
{
    protected $table = 'pembayaran_jadwal';

    protected $fillable = [
        'aktif',
        'jam',
        'tanggal_sumber',
        'last_run_at',
        'last_status',
        'last_message',
        'last_tanggal',
    ];

    protected $casts = [
        'aktif' => 'boolean',
        'last_run_at' => 'datetime',
        'last_tanggal' => 'date',
    ];
}
