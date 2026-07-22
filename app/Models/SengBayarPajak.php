<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SengBayarPajak extends Model
{
    protected $table = 'seng_bayar_pajak';

    protected $fillable = [
        'nopol',
        'nopol_',
        'nopol_lama',
        'tgl_bayar',
        'pkb_provinsi_jalan',
        'pkb_provinsi_tunggakan',
        'pkb_opsen_jalan',
        'pkb_opsen_tunggakan',
        'year',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'tgl_bayar' => 'date',
        'pkb_provinsi_jalan' => 'integer',
        'pkb_provinsi_tunggakan' => 'integer',
        'pkb_opsen_jalan' => 'integer',
        'pkb_opsen_tunggakan' => 'integer',
        'year' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
