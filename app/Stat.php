<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Watson\Rememberable\Rememberable;

class Stat extends Model
{
    use Rememberable;

    protected $guarded = ['id'];

    public function listing(  )
    {
        return $this->belongsTo( Listing::class );
    }

    public function scopePrimary($query )
    {
        return $query->where("primary", true);
    }
}
