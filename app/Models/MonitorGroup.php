<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MonitorGroup extends Model
{
    protected $fillable = ['name'];

    public function items()
    {
        return $this->hasMany(MonitorGroupItem::class, 'group_id');
    }

    // Get array of monitor IDs
    public function getMonitorIdsAttribute()
    {
        return $this->items->pluck('monitor_id')->toArray();
    }
}