<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Presale extends Model
{
    protected $table = ['event_presales'];
    protected $guarded = ['id'];
}
