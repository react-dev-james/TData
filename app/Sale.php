<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Watson\Rememberable\Rememberable;

class Sale extends Model
{
    use Rememberable;

    protected $guarded = [ 'id' ];
    protected $dates = ['sale_date'];
    protected $appends = ['nice_day','nice_date'];

    public function listing()
    {
        return $this->belongsTo( Listing::class );
    }

    public function getNiceDayAttribute()
    {
        return strtolower($this->day);
    }

    public function getNiceDateAttribute(  )
    {
        return $this->sale_date->format( 'D, M j, g:i A' );
    }

}
