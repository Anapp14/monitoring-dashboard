<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MonitoringRecord extends Model
{
    protected $fillable = [
        'monitor_id',
        'name',
        'type',
        'date',
        'uptime',
    ];
}
