<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Log;

class Listing extends Model
{

    use SoftDeletes;

    const CANADA_ADJUSTMENT = 0.76;
    protected $guarded = ['id'];
    protected $appends = ['nice_date','nice_sale_date','avg_sale_price','avg_sale_price_past','sold_per_event'];
    protected $dates = ['created_at','updated_at','event_date','first_onsale_date'];

    /* Adjustments for days of week */
    protected $daysOfWeek = [
        'Sunday'    => 0.94,
        'Monday'    => 0.86,
        'Tuesday'   => 0.82,
        'Wednesday' => 0.95,
        'Thursday'  => 1.02,
        'Friday'    => 1.08,
        'Saturday'  => 0.95
    ];

    public function data(  )
    {
        return $this->belongsToMany(\App\DataMaster::class, 'listing_data')->withPivot('confidence');
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

    public function getAvgSalePriceAttribute($data)
    {
        if ( !$data ) {
            Log::info('**** data not found for event: ' . $this->event_name);
            return 0;
        }

        Log::info('event day: ' . $this->event_day);
        Log::info('day of the week and day: ' . $this->daysOfWeek[$this->event_day]);
        if ( isset( $this->daysOfWeek[$this->event_day] ) ) {
            $dayAdjustment = $this->daysOfWeek[$this->event_day];

            Log::info('day adjustment: ' . $this->daysOfWeek[$this->event_day]);
            return round( $data->weighted_avg * $dayAdjustment );
        }

        return $data->weighted_avg;
    }

    public function getSoldPerEventAttribute()
    {
        $data = $this->data->first();
        if ( !$data ) {
            return 0;
        }

        return round($data->tot_per_event);

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

        // get data
        //$data = $listing->data->first();
        /* I am hacking this solution in for now where the $data isn't found in in this call for some reason.
            It seems like it has be be gotten once, then passed around.  I don't understand why now, but will check
            it out later.
         */

        // update weighted_sold
        $data = $listing->updateWeightedSold();

        if (!$data) {
            Log::info('$$$$$ data not found $$$$$$$');
            Log::info(print_r($data));
        }

        // set to 0 if data is not there
        if ( !$data || ( $data->total_sold ) <= 0 ) {
            \App\Stat::updateOrCreate( [ 'listing_id' => $listing->id ], [
                'roi_sh'     => 0,
                'roi_low'    => 0,
                'roi_net'    => 0,
                'listing_id' => $listing->id
            ] );
            return 0;
        }

        /* calculate rois */
        // set weighted sold
        $listing->refresh();
        $weighted_sold = $listing->weighted_sold;

        // set default rois
        $high_roi = 0;
        $low_roi = 0;
        $net_roi = 0;

        // set roi high
        if ($updateHigh && intval( $listing->high_ticket_price ) > 0) {
            $high_value = ($listing->high_ticket_price * 1.15) + 6;
            $roi = (($weighted_sold * .93) - $high_value) / $high_value;
            $high_roi = ceil($roi) > -1 ? ceil($roi * 100) : 0;

            $net_roi = ceil($high_roi * 40);

            if( $high_roi === 0 ) {
                Log::info('---- problem high roi ----');
                Log::info('listing id: ' . $listing->id);
                Log::info('listing event: ' . $listing->event_name);
                Log::info('ticket price: '  . $listing->high_ticket_price * 1.15);
                Log::info('high_value: ' . $high_value);
                Log::info('weighted_sold:' . $weighted_sold);
            } else {
                Log::info('--** high roi = ' . $high_roi);
                Log::info('--** roi = ' . $roi);
            }
        }

        // set roi low
        if ( $updateLow && intval( $listing->low_ticket_price ) > 0 ) {
            $low_value = ($listing->low_ticket_price * 1.15) + 6;
            $roi = (($weighted_sold * .93) - $low_value) / $low_value;
            $low_roi = ceil($roi) > -1 ? ceil($roi * 100) : 0;

            if( $low_roi === 0 ) {
                Log::info('---- problem high roi ----');
                Log::info('listing id: ' . $listing->id);
                Log::info('listing event: ' . $listing->event_name);
                Log::info('ticket price: '  . $listing->low_ticket_price * 1.15);
                Log::info('high_value: ' . $low_value);
                Log::info('weighted_sold:' . $weighted_sold);
            }
        }

        // save data
        Log::info('--** saving roi high at: ' . $high_roi);
        \App\Stat::updateOrCreate( [ 'listing_id' => $listing->id ], [
            'roi_sh'     => $high_roi,
            'roi_low'    => $low_roi,
            'roi_net'    => $net_roi,
            'listing_id' => $listing->id
        ] );

        return true;

    }

    /* not being used */
    public function updateSoldPerEvent()
    {
        return true;
        $data = $this->data->first();
        $stats = $this->stats()->first();
        if ( !$data || !$stats) {
            return 0;
        }

        $soldPerEvent = ( $data->total_sold + $stats->tix_sold_in_date_range  ) /
                        max( 1, ( $data->total_events + $stats->tn_events ) );
        $soldPerEvent = round( $soldPerEvent );

        \App\Stat::updateOrCreate( [ 'listing_id' => $this->id ], [
            'sold_per_event' => $soldPerEvent,
            'listing_id' => $this->id
        ] );

        return $soldPerEvent;

    }

    public function resetTicketNetwork() {
        \App\Stat::updateOrCreate( [ 'listing_id' => $this->id ], [
            'tix_sold_in_date_range'       => 0,
            'avg_sold_price_in_date_range' => 0
        ] );
    }

    /* this function is not being used */
    public function updateTicketNetworkStats($ticketsSold, $avgSoldPrice, $updateRoi = false)
    {
        return true;
        \App\Stat::updateOrCreate( [ 'listing_id' => $this->id ], [
            'tix_sold_in_date_range'       => round( $ticketsSold ),
            'avg_sold_price_in_date_range' => round( $avgSoldPrice )
        ] );

        if ($updateRoi) {
            $this->calcRoi();
            //$this->updateSoldPerEvent();
        }
    }

    public function updateWeightedSold()
    {
        $listing = $this;
        $data = $listing->data()->first();
        if (!$data ) {
            Log::info('-- data for ' . $listing->event_name . ' not found--');
            return false;
        }

        $weightedSold = $this->getAvgSalePriceAttribute($data);
        Log::info('--- weighted average calcuation for ' . $listing->event_name . ' ------');
        Log::info('weighted_avg: ' . $data->weighted_avg);
        Log::info('weighted sold:' . $weightedSold);

        $this->weighted_sold = $weightedSold;
        $this->save();

        return $data;
    }

}
