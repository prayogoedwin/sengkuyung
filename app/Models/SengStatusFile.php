<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SengStatusFile extends Model
{
    use HasFactory;

    protected $table = 'seng_status_file';

    protected $fillable = [
        'id_status',
        'nama_file',
        'type_file',
        'ukuran_file',
        'keterangan_file',
    ];

    public function status()
    {
        return $this->belongsTo(SengStatus::class, 'id_status');
    }
}
