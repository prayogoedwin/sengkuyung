<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RvfCacheSetting extends Model
{
    protected $table = 'rvf_cache_settings';

    protected $fillable = [
        'use_cache',
        'warm_channel',
        'ttl_hours',
        'ttl_detail_minutes',
        'schedule_enabled',
        'schedule_slots',
        'warm_year',
        'last_warm_started_at',
        'last_warm_finished_at',
        'last_warm_status',
        'last_warm_message',
    ];

    protected $casts = [
        'use_cache' => 'boolean',
        'schedule_enabled' => 'boolean',
        'ttl_hours' => 'integer',
        'ttl_detail_minutes' => 'integer',
        'schedule_slots' => 'array',
        'warm_year' => 'integer',
        'last_warm_started_at' => 'datetime',
        'last_warm_finished_at' => 'datetime',
    ];
}
