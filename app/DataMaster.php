<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DataMaster extends Model
{
    protected $guarded = ['id'];
    protected $table = 'data_master';


    public function listing()
    {
        return $this->hasMany( Listing::class, 'data_master_id', 'id' );
    }

    public function getTotalSalesAttribute( $value )
    {
        return round($value * 0.925);
    }

    public function getTotalSalesPastAttribute( $value )
    {
        return round($value * 0.925);
    }

}
