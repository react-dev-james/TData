<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class EventLookup extends Model
{
    protected $guarded = [ 'id' ];
    protected $table = 'event_lookups';
}
