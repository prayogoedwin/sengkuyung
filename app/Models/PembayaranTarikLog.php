<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PembayaranTarikLog extends Model
{
    protected $table = 'pembayaran_tarik_logs';

    protected $fillable = [
        'jenis',
        'tanggal_mulai',
        'tanggal_selesai',
        'tanggal_proses',
        'halaman',
        'total_halaman',
        'status',
        'inserted',
        'skipped_duplicate',
        'skipped_invalid',
        'message',
        'created_by',
        'started_at',
        'finished_at',
    ];

    protected $casts = [
        'tanggal_mulai' => 'date',
        'tanggal_selesai' => 'date',
        'tanggal_proses' => 'date',
        'halaman' => 'integer',
        'total_halaman' => 'integer',
        'inserted' => 'integer',
        'skipped_duplicate' => 'integer',
        'skipped_invalid' => 'integer',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];
}
