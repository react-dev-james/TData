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
    protected $appends = ['nice_date','nice_sale_date','sold_per_event'];
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

    public function data()
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

    public function getAvgSalePrice($data)
    {
        if ( !$data ) {
            Log::info('**** data not found for event: ' . $this->event_name);
            throw new \Exception('**** data not found for event: ' . $this->event_name);
            return 0;
        }

        if ( isset( $this->daysOfWeek[$this->event_day] ) ) {
            $dayAdjustment = $this->daysOfWeek[$this->event_day];
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
        if ( $this->first_onsale_date !== null ) {
            return $this->first_onsale_date->format( 'D, M j, g:i A' );
        }

        //Log::info('--- no nice sale date ---');
        //Log::info($this->first_onsale_date);
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

    public function calcRoi($data)
    {
        $listing = $this;

        /* I am hacking this solution in for now where the Data model isn't found in in this call for some reason.
            Especially in the children method calls.It appears to randomly not show up.
            It is like there is a timing issue when the data is associated.
            So, the simple solution here is to pass in the Data model as we always have it when calcRoi method
            is called.

            The real solution is to change the relationship to Data model to one to many
         */
        // get data
        //$data = $listing->data->first();

        // update weighted_sold
        $listing->updateWeightedSold($data);

        if (!$data) {
            Log::info('$$$$$ data not found $$$$$$$');
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
        if ( intval( $listing->high_ticket_price ) > 0) {
            $high_value = ($listing->high_ticket_price * 1.2) + 6;
            $roi = (($weighted_sold * .94) - $high_value) / $high_value;
            $high_roi = ceil($roi * 100);

            $net_roi = $high_roi > 0 ? ceil((($weighted_sold * .94) - $high_value) * 40) : 0;

            /* disable logging for now
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
            */
        }

        // set roi low
        if ( intval( $listing->low_ticket_price ) > 0 ) {
            $low_value = ($listing->low_ticket_price * 1.2) + 6;
            $roi = (($weighted_sold * .94) - $low_value) / $low_value;
            $low_roi = ceil($roi * 100);

            /* disable logging for now
            if( $low_roi === 0 ) {
                Log::info('---- problem high roi ----');
                Log::info('listing id: ' . $listing->id);
                Log::info('listing event: ' . $listing->event_name);
                Log::info('ticket price: '  . $listing->low_ticket_price * 1.15);
                Log::info('high_value: ' . $low_value);
                Log::info('weighted_sold:' . $weighted_sold);
            }
            */
        }

        // save data
        //Log::info('--** saving roi high at: ' . $high_roi);
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

    public function updateWeightedSold($data)
    {
        $listing = $this;
        //$data = $listing->data()->first();
        if (!$data ) {
            Log::info('-- data for ' . $listing->event_name . ' not found--');
            return false;
        }

        $weightedSold = $this->getAvgSalePrice($data);

        /* disable logging for now
        Log::info('--- weighted average calcuation for ' . $listing->event_name . ' ------');
        Log::info('weighted_avg: ' . $data->weighted_avg);
        Log::info('weighted sold:' . $weightedSold);
        */

        $this->weighted_sold = $weightedSold;
        $this->save();
    }

}
