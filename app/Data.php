<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Data extends Model
{
    protected $guarded = ['id'];
    protected $table = 'data';

    public function listing()
    {
        return $this->belongsToMany( \App\Data::class, 'listing_data' );
    }
}
