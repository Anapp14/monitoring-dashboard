<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MonitorGroupItem extends Model
{
    protected $fillable = ['group_id', 'monitor_id'];

    public function group()
    {
        return $this->belongsTo(MonitorGroup::class, 'group_id');
    }
}