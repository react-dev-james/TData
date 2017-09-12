<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Listing extends Model
{

    protected $guarded = ['id'];
    protected $appends = ['source_link'];


    public function stats()
    {
        return $this->hasOne( Stat::class )->where("primary", true);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function updates()
    {
        return $this->hasOne( Update::class );
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
