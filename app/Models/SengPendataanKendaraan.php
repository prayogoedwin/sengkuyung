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
        'desa_name', 'kec_name', 'kota_name', 'prov_name', 
        'desa', 'kel_dagri',
        'kec', 'kec_dagri',
        'kota', 'kota_dagri',
        'provinsi',
        'merk', 'tipe', 'tahun', 'tnkb', 'warna', 'jenis_kbm', 'jatuh_tempo',
        'pkb_pokok', 'pkb_denda', 'pkb', 'tanggal_akhir_Pkb',
        'jr_pokok', 'jr_denda', 'jr', 'pnbp_stnk', 'pnbp_tnkb', 'pnbp',
        'is_setuju', 'ttd', 'lat', 'lng', 'status', 'status_name', 'status_verifikasi',
        'status_verifikasi_name', 'created_by', 'updated_by', 'deleted_by',
        'file0', 'file0_url', 'file0_ket', 'file0_encrypted', 'file0_original_ext',
        'file1', 'file1_url', 'file1_ket', 'file1_encrypted', 'file1_original_ext',
        'file2', 'file2_url', 'file2_ket', 'file2_encrypted', 'file2_original_ext',
        'file3', 'file3_url', 'file3_ket',
        'file4', 'file4_url', 'file4_ket',
        'file5', 'file5_url', 'file5_ket',
        'file6', 'file6_url', 'file6_ket',
        'file7', 'file7_url', 'file7_ket',
        'file8', 'file8_url', 'file8_ket',
        'file9', 'file9_url', 'file9_ket',
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

    public function kabkota()
    {
        return $this->belongsTo(SengWilayah::class, 'kota', 'id');
    }

    public function wilayah()
    {
        return $this->belongsTo(SengWilayah::class, 'kec', 'id');
    }

    public function createdByUser()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }
    
}
