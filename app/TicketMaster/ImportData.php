<?php
/**
 * Created by PhpStorm.
 * User: sungwhikim
 * Date: 30/08/2018
 * Time: 18:44
 */

namespace App\TicketMaster;

use App\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ImportData
{
    private $ticket_master;

    public function loadAllData()
    {
        // init ticket master
        $this->ticket_master = new TicketMaster(config('api.ticket_master.keys')[0]);

        // get dates
        $start_date = Carbon::now();
        $start_date->startOfWeek();

        // --todo -- if api call limit is close, change to another api key -> do in the curl call

        // run for each day of the week
        for( $day_of_week = 0; $day_of_week < 7; $day_of_week++ )
        {
            // set start date
            $event_start_date = $start_date->toDateString();
            echo "--- TM.Import data for date: " . $event_start_date . "---------\n";

            $this->loadEvents($event_start_date);

            // increment date
            $start_date->addDays(1);
        }

        echo "-------- total calls made = " . $this->ticket_master->calls_made . "---------\n";
    }

    private function loadEvents($day)
    {
        /** we need to loop for each country? */
        // init page variable
        $current_page = 0;
        $total_pages = 1;

        // loop to paginate
        do {
            // set payload
            $payload = [
                'onsaleOnStartDate'  => $day,
                'countryCode'        => 'us',
                'size'               => config('api.ticket_master.page_size'),
                'page'               => $current_page,
                // -- todo -- add more exclusions here --//
                'classificationName' => '-sports'
            ];

            // get events
            $events_data = $this->ticket_master->eventSearch($payload);

            // exit if no event data is found
            if( !isset($events_data->_embedded->events) ) {
                return false;
            }

            // get exclusions
            $exclusions = config('api.event_exclusions');

            // loop through each event
            foreach ( $events_data->_embedded->events as $event_data ) {
                // set tm event id
                $tm_event_id = $event_data->id;

                // check exclusion list and remove if the event is there then continue to next item
                foreach ( $exclusions as $exclusion ) {
                    if ( stripos($event_data->name, $exclusion) !== false ) {

                        Event::where('tm_id', '=', $tm_event_id)->delete();
                        echo '-- skipped: ' . $event_data->name, "---\n";
                        continue 2;
                    }
                }

                // set basic data
                $payload = [
                    'name'                 => $event_data->name,
                    'type'                 => $event_data->type,
                    'url'                  => $event_data->url,
                    'locale'               => $event_data->locale,
                    'currency'             => isset($event_data->priceRanges->currency) ? $event_data->priceRanges->currency : null,
                    'public_sale_datetime' => $event_data->sales->public->startDateTime,
                    'presale_name'         => isset($event_data->sales->presales->name) ? $event_data->sales->presales->name : null,
                    'presale_datetime'     => isset($event_data->sales->presales->startDateTime) ? $event_data->sales->presales->startDateTime : null,
                    'sales_start_tbd'      => $event_data->sales->public->startTBD,
                    'event_local_date'     => $event_data->dates->start->localDate,
                    'event_local_time'     => isset($event_data->dates->start->localTime) ? $event_data->dates->start->localTime : null,
                    'event_time_zone'      => isset($event_data->dates->timezone) ? $event_data->dates->timezone : null,
                    'event_datetime'       => isset($event_data->dates->start->dateTime) ? $event_data->dates->start->dateTime : null,
                    'event_status_code'    => $event_data->dates->status->code,
                    'ticket_limit'         => isset($event_data->ticketLimit->info) ? $event_data->ticketLimit->info : null,
                ];

                // set segment
                $payload['segment_id'] = isset($event_data->classifications[0]->segment->id) ?
                    $this->getSegment(
                        $event_data->classifications[0]->segment->id,
                        $event_data->classifications[0]->segment->name
                    )->id : null;

                // set genre
                $payload['genre_id'] = isset($event_data->classifications[0]->genre->id) ?
                    $this->getGenre(
                        $event_data->classifications[0]->genre->id,
                        $event_data->classifications[0]->genre->name
                    )->id : null;

                // set sub-genre
                $payload['sub_genre_id'] = isset($event_data->classifications[0]->subGenre->id) ?
                    $this->getSubGenre(
                        $event_data->classifications[0]->subGenre->id,
                        $event_data->classifications[0]->subGenre->name
                    )->id : null;

                // save event
                $event = (new \App\Event)->updateOrCreate(
                    ['tm_id' => $tm_event_id],
                    $payload
                );

                // get/set venues
                $this->setVenues($event->id, $event_data);

                // get/set attractions
                $this->setAttractions($event->id, $event_data);

                // get/set prices only if the event was created or if no prices were set
                if( $event->wasRecentlyCreated === true
                    || (new \App\EventPrice())->where('event_id', '=', $event->id)->count() === 0 ) {

                    // check to see if it was a create or update
                    if( env('API_DEBUG') ) {
                        echo '-- was created recently = ' . $event->wasRecentlyCreated . " : " . $event->id . "---\n";
                        echo '* event price count = ' . (new \App\EventPrice())->where('event_id', '=', $event->id)->count() . "\n";
                    }

                    $this->setPrices($event->id, $tm_event_id);
                }
            }

            // set total pages and increment
            $total_pages = $events_data->page->totalPages;
            $current_page++;

            echo "current page is: $current_page\n";
            echo "total pages is: $total_pages\n";
        } while( $current_page < $total_pages );
    }

    private function getSegment($segment_id, $segment_name)
    {
        $segment = (new \App\Segment())->firstOrCreate(
            ['tm_id' => $segment_id],
            ['name' => $segment_name]
        );

        return $segment;
    }

    private function getGenre($genre_id, $genre_name)
    {
        $genre = (new \App\Genre())->firstOrCreate(
            ['tm_id' => $genre_id],
            ['name' => $genre_name]
        );

        return $genre;
    }

    private function getSubGenre($sub_genre_id, $sub_genre_name)
    {
        $sub_genre = (new \App\SubGenre())->firstOrCreate(
            ['tm_id' => $sub_genre_id],
            ['name' => $sub_genre_name]
        );

        return $sub_genre;
    }

    private function setVenues($event_id, $event_data)
    {
        if( isset($event_data->_embedded->venues[0]) ) {
            $venue_ids = [];

            foreach( $event_data->_embedded->venues as $venue_data)
            {
                $venue = (new \App\Venue())->updateOrCreate(
                    ['tm_id' => $venue_data->id],
                    [
                        'name' => $venue_data->name,
                        'url' => isset($venue_data->url) ? $venue_data->url : null,
                        'locale' => isset($venue_data->locale) ? $venue_data->locale : null,
                        'postal_code' => isset($venue_data->postalCode) ? $venue_data->postalCode : null,
                        'time_zone' => isset($venue_data->timezone) ? $venue_data->timezone : null,
                        'city' => isset($venue_data->city->name) ? $venue_data->city->name : null,
                        'state_name' => isset($venue_data->state->name) ? $venue_data->state->name : null,
                        'state_code' => isset($venue_data->state->stateCode) ? $venue_data->state->stateCode : null,
                        'country_code' => isset($venue_data->country->countryCode) ? $venue_data->country->countryCode : null,
                        'address' => isset($venue_data->address->line1) ? $venue_data->address->line1 : null,
                        'longitude' => isset($venue_data->location->longitude) ? $venue_data->location->longitude : null,
                        'latitude' => isset($venue_data->location->latitude) ? $venue_data->location->latitude : null,
                        'api_url' => isset($venue_data->_links->self->href) ? $venue_data->_links->self->href : null,
                    ]
                );

                $venue_ids[] = $venue->id;
            }

            /* Update x-ref table.  We are doing this manually as using the relationship in the model is not reliable. */
            // delete all existing relationships
            DB::table('event_venue')->where('event_id', '=', $event_id)->delete();

            // add the relationships
            for( $index = 0; $index < count($venue_ids); $index++ )
            {
                // insert
                DB::table('event_venue')
                    ->insert([
                        'event_id' => $event_id,
                        'venue_id' => $venue_ids[$index],
                        'primary' => $index === 0 ? true : false
                    ]);
            }
        }
    }

    private function setAttractions($event_id, $event_data)
    {
        if( isset($event_data->_embedded->attractions[0]) ) {
            $attraction_ids = [];

            foreach( $event_data->_embedded->attractions as $attraction_data)
            {
                // set basic data
                $payload = [
                    'name' => $attraction_data->name,
                    'type' => isset($attraction_data->type) ? $attraction_data->type : null,
                    'url' => isset($attraction_data->url) ? $attraction_data->url : null,
                    'locale' => isset($attraction_data->locale) ? $attraction_data->locale : null,
                    'upcoming_events' => isset($attraction_data->upcomingEvents->_total) ?
                        $attraction_data->upcomingEvents->_total : null,
                    'api_url' => isset($attraction_data->_links->self->href) ? $attraction_data->_links->self->href : null,
                ];

                /* get classifications */
                // set segment
                $payload['segment_id'] = isset($attraction_data->classifications[0]->segment->id) ?
                    $this->getSegment(
                        $attraction_data->classifications[0]->segment->id,
                        $attraction_data->classifications[0]->segment->name
                    )->id : null;

                // set genre
                $payload['genre_id'] = isset($attraction_data->classifications[0]->genre->id) ?
                    $this->getGenre(
                        $attraction_data->classifications[0]->genre->id,
                        $attraction_data->classifications[0]->genre->name
                    )->id : null;

                // set sub-genre
                $payload['sub_genre_id'] = isset($attraction_data->classifications[0]->subGenre->id) ?
                    $this->getSubGenre(
                        $attraction_data->classifications[0]->subGenre->id,
                        $attraction_data->classifications[0]->subGenre->name
                    )->id : null;

                // set data
                $attraction = (new \App\Attraction())->updateOrCreate(
                    ['tm_id' => $attraction_data->id],
                    $payload
                );

                // set for adding to x-ref table
                $attraction_ids[] = $attraction->id;

                // set social media links only if this attraction was created
                if( $attraction->wasRecentlyCreated === true ) {

                    $this->setExternalLinks($attraction->id, $attraction_data);
                }
            }

            /* Update x-ref table.  We are doing this manually as using the relationship in the model is not reliable. */
            // delete all existing relationships
            DB::table('event_attraction')->where('event_id', '=', $event_id)->delete();

            // add the relationships
            for( $index = 0; $index < count($attraction_ids); $index++ )
            {
                // insert
                DB::table('event_attraction')
                    ->insert([
                        'event_id' => $event_id,
                        'attraction_id' => $attraction_ids[$index],
                        'primary' => $index === 0 ? true : false
                    ]);
            }
        }

        // debug
        else {
            if( env('API_DEBUG') ) {
                 echo '/--- attraction not found for ' . $event_data->name . "---/\n";
                 echo $event_data->_links->self->href . "\n";
            }
        }
    }

    private function setExternalLinks($attraction_id, $attraction_data)
    {
        // check if external links exist
        if( isset($attraction_data->externalLinks) ) {

            // loop through each external link
            foreach( $attraction_data->externalLinks as $key => $value )
            {
                // get the social media type
                $social_media_type = (new \App\SocialMediaType())->firstOrCreate(
                    ['name' => strtolower($key)]
                );

                // update or create
                if( isset($value[0]->url) ) {

                    // we are using straight db calls as using the SocialMedia model is not working.  It is easier and
                    // faster than finding the weird setting or other eloquent(not so eloquent) idiosyncrasies.
                    // -- todo --- figure out why a simple query on the model doesn't work.

                    // check if this social media record exists
                    $social_media = DB::table('social_medias')
                        ->where('social_media_type_id', '=', $social_media_type->id)
                        ->where('attraction_id', '=', $attraction_id)
                        ->first();

                    // insert new
                    if( $social_media === null ) {
                        DB::table('social_medias')->insert([
                            'social_media_type_id' => $social_media_type->id,
                            'attraction_id' => $attraction_id,
                            'url' => $value[0]->url,
                            'created_at' => date("Y-m-d H:i:s"),
                            'updated_at' => date("Y-m-d H:i:s"),
                        ]);
                    }

                    // update
                    else {
                        DB::table('social_medias')
                            ->where('social_media_type_id', '=', $social_media_type->id)
                            ->where('attraction_id', '=', $attraction_id)
                            ->update([
                                'url' => $value[0]->url,
                                'updated_at' => date("Y-m-d H:i:s"),
                            ]);
                    }
                }
            }
        }
    }

    private function setPrices($event_id, $tm_event_id)
    {
        // get the offers
        $offer_data = $this->ticket_master->getEventOffers($tm_event_id);

        // only process if there is data
        if( isset($offer_data->offers[0]) ) {

            // check each offer for the default
            foreach( $offer_data->offers as $offer )
            {
                // check for the default offer and that prices exists
                if( isset($offer->attributes->offerType)
                        && strtolower($offer->attributes->offerType) === 'default'
                            && isset($offer->attributes->prices[0]) ) {

                    // delete all previous prices - we don't need to delete as we only do this if no prices are set
                    // (new \App\EventPrice())->where('event_id', '=', $event_id)->delete();

                    // insert prices
                    foreach( $offer->attributes->prices as $price )
                    {
                        (new \App\EventPrice())->create([
                            'event_id' => $event_id,
                            'price_zone' => $price->priceZone,
                            'currency' => $offer->attributes->currency,
                            'value' => $price->value,
                            'total' => $price->total,
                        ]);
                    }

                    // break loop since we found valid offer prices
                    break;
                }
            }

        }
    }
}