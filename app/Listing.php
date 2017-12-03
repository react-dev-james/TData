<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Listing extends Model
{

    use SoftDeletes;

    const CANADA_ADJUSTMENT = 0.8;
    protected $guarded = ['id'];
    protected $appends = ['nice_date','nice_sale_date','avg_sale_price','avg_sale_price_past','sold_per_event'];
    protected $dates = ['created_at','updated_at','event_date','first_onsale_date'];

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

    public function data(  )
    {
        return $this->belongsToMany(\App\Data::class, 'listing_data')->withPivot('confidence');
    }

    public function stats()
    {
        return $this->hasOne( Stat::class );
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function updates()
    {
        return $this->hasOne( Update::class );
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function sales(  )
    {
        return $this->hasMany(\App\Sale::class)->orderBy('sale_date','ASC');
    }


    public function getLowTicketPriceAttribute( $value )
    {
        if ( $this->venue_country == 'CA') {
            return number_format( round( $value * self::CANADA_ADJUSTMENT ), 0, '.', '' );
        }

        return number_format( $value, 0, '.', '' );
    }

    public function getHighTicketPriceAttribute( $value )
    {
        if ( $this->venue_country == 'CA' ) {
            return number_format( round( $value * self::CANADA_ADJUSTMENT ), 0, '.', '' );
        }

        return number_format( $value, 0, '.', '' );
    }

    public function getAvgTicketPriceAttribute( $value )
    {
        if ( $this->venue_country == 'CA' ) {
            return number_format( round( $value * self::CANADA_ADJUSTMENT ), 0, '.', '' );
        }

        return number_format( $value, 0, '.', '' );
    }

    public function getAvgSalePriceAttribute()
    {
        $data = $this->data->first();
        if ( !$data ) {
            return 0;
        }

        if ( isset( $this->daysOfWeek[$this->event_day] ) ) {
            $dayAdjustment = $this->daysOfWeek[$this->event_day];
            return round( $data->avg_sale_price * $dayAdjustment );
        }

        return $data->avg_sale_price;
    }

    public function getSoldPerEventAttribute()
    {
        $data = $this->data->first();
        if ( !$data ) {
            return 0;
        }

        $soldPerEvent = ($data->total_sales + $data->total_sales_past) / max(1, ($data->upcoming_events + $data->past_events) );

        return round($soldPerEvent);

    }

    public function getAvgSalePricePastAttribute( )
    {
        $data = $this->data->first();
        if ( !$data ) {
            return 0;
        }

        if ( isset( $this->daysOfWeek[$this->event_day] ) ) {
            $dayAdjustment = $this->daysOfWeek[$this->event_day];
            return round( $data->avg_sale_price_past * $dayAdjustment );
        }

        return $data->avg_sale_price_past;
    }

    public function getSalesStatsAttribute(  )
    {
        return $this->data()->first();
    }

    public function getLookupsAttribute()
    {
        if ($this->data->count() == 0) {
            return [];
        }

        return \App\EventLookup::where("event_slug",$this->slug)->where("match_slug", $this->data->first()->category_slug)->get();
    }

    public function getNiceDateAttribute()
    {
        return $this->event_date->toDayDateTimeString();
    }

    public function getNiceSaleDateAttribute()
    {
        if ( $this->first_onsale_date ) {
            return $this->first_onsale_date->format( 'D, M j, g:i A' );
        }

        return "N/A";
    }

    public function recordUpdate( $type )
    {
        $dateField = $type . "_at";
        $timesField = $type . "_times";

        /* Create and record update if it doesn't exist */
        if ( $this->updates()->count() == 0 ) {
            $this->updates()->create( [
                $dateField  => date( "Y-m-d H:i:s" ),
                $timesField => 1
            ] );
            return;
        }

        /* Update date/times for existing update record */
        $update = $this->updates()->first();
        $update->{$dateField} = date( "Y-m-d H:i:s" );
        $update->{$timesField} = $update->{$timesField} + 1;
        $update->save();
    }

    public function calcRoi($updateHigh = true, $updateLow = true)
    {
        $listing = $this;
        $data = $listing->data->first();
        $stats = $listing->stats()->first();
        if ( !$data || ( $data->total_sales + $data->total_sales_past ) <= 0 ) {
            \App\Stat::updateOrCreate( [ 'listing_id' => $listing->id ], [
                'roi_sh'     => 0,
                'roi_low'    => 0,
                'roi_net'    => 0,
                'listing_id' => $listing->id
            ] );
            return 0;
        }

        if (!$stats) {
            return 0;
        }

        $highRoi = 0;
        $lowRoi = 0;
        $netRoi = 0;
        if ($updateHigh && intval( $listing->high_ticket_price ) > 0) {
            $total = ( $this->getAvgSalePriceAttribute() * $data->total_sales )
                    + ( $this->getAvgSalePricePastAttribute() * $data->total_sales_past )
                    + ($stats->avg_sold_price_in_date_range * $stats->tix_sold_in_date_range);

            $roi = ( $total / ( $data->total_sales + $data->total_sales_past + $stats->tix_sold_in_date_range )) / ( intval( $listing->high_ticket_price ) * 1.15 + 6 );
            $highRoi = round( ( $roi - 1 ) * 100 );
            $netRoi = max(0,round(($roi - 1) * ( intval( $listing->high_ticket_price ) * 1.15 + 6) * 40));
        }

        if ( $updateLow && intval( $listing->low_ticket_price ) > 0 ) {
            $total =
                ( $this->getAvgSalePriceAttribute() * $data->total_sales )
                + ( $this->getAvgSalePricePastAttribute() * $data->total_sales_past )
                + ( $stats->avg_sold_price_in_date_range * $stats->tix_sold_in_date_range );

            $roi = ( $total / ( $data->total_sales + $data->total_sales_past + $stats->tix_sold_in_date_range ) ) / ( intval( $listing->low_ticket_price ) * 1.15 + 6 );
            $lowRoi = round( ( $roi - 1 ) * 100 );
        }

        \App\Stat::updateOrCreate( [ 'listing_id' => $listing->id ], [
            'roi_sh'     => $highRoi,
            'roi_low'    => $lowRoi,
            'roi_net'    => $netRoi,
            'listing_id' => $listing->id
        ] );

        return true;

    }

    public function updateSoldPerEvent()
    {
        $data = $this->data->first();
        if ( !$data ) {
            return 0;
        }

        $soldPerEvent = ( $data->total_sales + $data->total_sales_past ) / max( 1, ( $data->upcoming_events + $data->past_events ) );
        $soldPerEvent = round( $soldPerEvent );

        \App\Stat::updateOrCreate( [ 'listing_id' => $this->id ], [
            'sold_per_event' => $soldPerEvent,
            'listing_id' => $this->id
        ] );

        return $soldPerEvent;

    }

    public function updateTicketNetworkStats($ticketsSold, $avgSoldPrice, $updateRoi = false)
    {
        \App\Stat::updateOrCreate( [ 'listing_id' => $this->id ], [
            'tix_sold_in_date_range' => round($ticketsSold),
            'avg_sold_price_in_date_range'     => round($avgSoldPrice)
        ] );

        if ($updateRoi) {
            $this->calcRoi();
        }
    }

}
