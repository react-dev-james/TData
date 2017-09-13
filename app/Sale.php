<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Watson\Rememberable\Rememberable;

class Sale extends Model
{
    use Rememberable;

    protected $guarded = [ 'id' ];

    public function listing()
    {
        return $this->belongsTo( Listing::class );
    }

}
