<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    protected $guarded = ['id'];

    public function state()
    {
        return $this->belongsTo(EventState::class, 'event_state_id', 'id');
    }
}
