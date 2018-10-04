<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ListingsView extends Model
{
    protected $guarded = ['event_id'];
    protected $table = 'listings_view';

    public function state()
    {
        return $this->belongsTo(EventState::class, 'event_state_id', 'id');
    }
}
