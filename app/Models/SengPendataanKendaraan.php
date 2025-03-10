<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SengPendataanKendaraan extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'seng_pendataan_kendaraan';

    protected $fillable = [
        'nohp', 'email', 'nik', 'tgl_ctk', 'nopol', 'nama', 'alamat',
        'desa_name', 'kec_name', 'kota_name', 'prov_name', 'desa', 'kec', 'kota', 'provinsi',
        'merk', 'tipe', 'tahun', 'tnkb', 'warna', 'jenis_kbm', 'jatuh_tempo',
        'pkb_pokok', 'pkb_denda', 'pkb', 'tanggal_akhir_Pkb',
        'jr_pokok', 'jr_denda', 'jr', 'pnbp_stnk', 'pnbp_tnkb', 'pnbp',
        'is_setuju', 'ttd', 'status', 'status_name', 'status_verifikasi',
        'status_verifikasi_name', 'created_by', 'updated_by', 'deleted_by'
    ];

    protected $dates = ['deleted_at'];

    protected $attributes = [
        'deleted_by' => null, // Set default NULL
    ];

    // Contoh relasi ke tabel lain (jika ada hubungan dengan tabel lain)
    public function status()
    {
        return $this->belongsTo(SengStatus::class, 'status', 'id');
    }

    public function status_verifikasi()
    {
        return $this->belongsTo(SengStatusVerifikasi::class, 'status_verifikasi', 'id');
    }

    public function wilayah()
    {
        return $this->belongsTo(SengWilayah::class, 'kec', 'id');
    }
    
}
