<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Data extends Model
{
    protected $guarded = ['id'];
    protected $table = 'data';
    /* Adjustments for days of week */
    protected $daysOfWeek = [
        'Sunday'    => 0.89,
        'Monday'    => 0.85,
        'Tuesday'   => 0.86,
        'Wednesday' => 0.86,
        'Thursday'  => 0.97,
        'Friday'    => 1.02,
        'Saturday'  => 1.15
    ];

    public function listing()
    {
        return $this->belongsToMany( \App\Data::class, 'listing_data' );
    }

    public function getAvgSalesPriceAttribute( $value )
    {
        if (!$this->listing->first()) {
            return $value;
        }

        if ( isset( $this->daysOfWeek[$this->listing->first()->event_day] ) ) {
            $dayAdjustment = $this->daysOfWeek[$this->listing->first()->event_day];
            return round( $this->avg_sale_price * $dayAdjustment );
        }

        return $value;
    }

    public function getAvgSalesPricePastAttribute( $value )
    {
        if ( !$this->listing->first() ) {
            return $value;
        }

        if ( isset( $this->daysOfWeek[$this->listing->first()->event_day] ) ) {
            $dayAdjustment = $this->daysOfWeek[$this->listing->first()->event_day];
            return round( $this->avg_sale_price_past * $dayAdjustment );
        }

        return $value;
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
