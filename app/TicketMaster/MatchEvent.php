<?php

namespace App\TicketMaster;

use App\Event;
use App\DataMaster;

class MatchEvent
{
    public static function runMatch()
    {
        // -- todo -- need to add event status to not include excluded or archived events
        // get all the events that have no match so far and only the primary events
        $events = Event::select('events.id', 'events.name as event_name', 'attractions.name AS attraction_name')
            ->leftJoin('event_attraction', function ($join) {
                $join->on('event_attraction.event_id', '=', 'events.id');
            })
            ->leftJoin('attractions', function ($join) {
                $join->on('event_attraction.attraction_id', '=', 'attractions.id');
            })
            ->whereNull('events.data_master_id')
            ->where('event_attraction.primary', '=', true)
            ->get();

        // loop through and try to find match in the data master table
        foreach( $events as $event ) {
            // first try for exact matches
            $data_master = DataMaster::where('category', 'ilike', $event->attraction_name)
                ->orWhere('category', '=', $event->name)
                ->first();

            // found exact match
            if ( $data_master !== null ) {

                $event->data_master_id = $data_master->id;
                $event->match_confidence = 100;
                $event->save();
            }
            // since an exact match is not found, then try an ilike match
            else if( strlen($event->attraction_name) >= 3) {
                // get any rows from the data_master table where the names are close. Don't include any where the
                // length is 5 or less
                $data_masters = DataMaster::where('category', 'ilike', '%' . $event->attraction_name . '%')
                    ->whereRaw('char_length(category) >= 5')
                    ->get();

                // go through all the rows and set the best match
                $matched_data_master_id = null;
                $matched_confidence = 0;
                foreach( $data_masters as $data_master )
                {
                    // -- todo -- this is not a great way to match
                    // instead we need to match the words to the words in the other string and get the percentage

                    // get # string not the same
                    $not_same = levenshtein(strtolower($event->attraction_name), strtolower($data_master->category));

                    // get base string length
                    $string_length = strlen($data_master->category);

                    // calculate confidence by the % of matching string
                    $confidence = ceil(($string_length - $not_same / $string_length));

                    //echo $confidence . ';';
                    if( $confidence > $matched_confidence ) {
                        $matched_data_master_id = $data_master->id;
                        $matched_confidence = $confidence;
                    }
                }

                // save if there were any matches
                if( $matched_data_master_id !== null ) {

                    // save data
                    $event->data_master_id = $matched_data_master_id;
                    $event->match_confidence = $matched_confidence;
                    $event->save();
                }
            }
        }

    }

}