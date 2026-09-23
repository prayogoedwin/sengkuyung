<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IntegrasiSetting extends Model
{
    protected $table = 'integrasi_settings';

    protected $fillable = [
        'nama_aplikasi',
        'client_id',
        'secret_key',
        'api_key',
        'base_url',
    ];
}
