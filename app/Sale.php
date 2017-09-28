<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Watson\Rememberable\Rememberable;

class Sale extends Model
{
    use Rememberable;

    protected $guarded = [ 'id' ];
    protected $dates = ['sale_date'];

    public function listing()
    {
        return $this->belongsTo( Listing::class );
    }

}
