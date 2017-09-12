<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Update extends Model
{
    protected $guarded = ['id'];
    protected $dates = ['stats_at','rates_at','properties_at','listings_at','fetched_at'];
    protected $table = 'updates';
}
