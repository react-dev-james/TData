<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class EventState extends Model
{
    protected $guarded = ['id'];
    protected $table = 'event_states';
}
