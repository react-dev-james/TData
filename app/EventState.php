<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class EventState extends Model
{
    protected $guarded = ['id'];
    protected $table = 'event_states';

    public function event()
    {
        return $this->hasMany(Event::class);
    }

    public function active_state_id()
    {
        return $this->getStateId('active');
    }

    public function targeted_state_id()
    {
        return $this->getStateId('targeted');
    }

    public function excluded_state_id()
    {
        return $this->getStateId('excluded');
    }

    public function getStateId($slug)
    {
        return $this->where('slug', '=', $slug)->value('id');
    }
}
