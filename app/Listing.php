<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Listing extends Model
{

    protected $guarded = ['id'];
    protected $appends = ['nice_date'];
    protected $dates = ['created_at','updated_at','event_date'];

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
        return $this->hasMany(\App\Sale::class);
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

}
