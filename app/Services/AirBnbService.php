<?php

namespace App\Services;

use \App\Location;
use \App\Listing;
use \App\Rate;
use \App\RateChange;
use \App\Booking;

use Illuminate\Support\Collection;

class AirBnbService extends ScraperService implements IScraper
{

    /**
     * @var \App\Location
     */
    protected $location;
    protected $apiKey;

    const SOURCE_IDENT = "airbnb";
    CONST LOCATION_URL = "https://www.airbnb.ca/search/search_results?guests=1&adults=1&children=0&infants=0&source=bb&location={city}%2C+{state}%2C+{country}&page={page}&currency=USD";
    CONST LISTINGS_URL = "https://www.airbnb.ca/search/search_results?guests=1&adults=1&children=0&infants=0&source=bb&location={city}%2C+{state}%2C+{country}&page={page}&currency=USD";
    const LISTING_DETAILS_URL = "https://www.airbnb.ca/rooms/{site_id}?guests=1&currency=USD";
    const LISTING_PRICE_URL = "https://www.airbnb.ca/api/v2/pricing_quotes?guests=1&listing_id={site_id}&_format=for_detailed_booking_info_on_web_p3_with_message_data&&check_in=2017-02-28&check_out=2017-03-02&number_of_adults=1&number_of_children=0&number_of_infants=0&key={api_key}&currency=USD&locale=en-US";
    const LISTING_SCHEDULE_URL = "https://www.airbnb.ca/api/v2/calendar_months?key={api_key}&currency=USD&locale=en-CA&listing_id={site_id}&month={start_month}&year={year}&count={num_months}&_format=with_conditions";

    public function setLocation( Location $location )
    {
        $this->location = $location;
        return $this;
    }

    public function updateAllStats(  )
    {
        $listings = Listing::all();
        foreach ($listings as $listing) {
            $listing->updateStats();
            echo "Listing stats updated...";
        }

        $locations = Location::all();
        foreach ($locations as $location) {
            $location->updateStats();
            echo "Location states updated...";
        }
        return $this;
    }

    public function addNewLocation($city, $state, $page = 1, $numPages = 10, $throttle = 1, $country = "United States")
    {
        if (!$this->getLocation($city, $state, $country)) {
            $this->display("No location data found for {$city}, {$state} ");
            return;
        }
        $this->display("Location data fetched for " . $city);
        $this->getAllListings( $page, $numPages, $throttle );
        $this->display("Listing data fetched for {$city}, {$country}");

    }

    public function updateMissingListingRates($limit = 30, $throttle=1) {
        $listings = Listing::doesntHave("rates")->limit($limit)->get();

        foreach ($listings as $listing) {
            try {

                echo ".";

                if ( $listing->rates()->count() == 0 ) {
                    $this->getListingDetails( $listing );
                    $this->display( "Rates fetched for " . $listing->id );
                    sleep($throttle);
                }

                $this->saveBookings( $listing );
                $listing->updateStats();
                $this->display( "Stats updated for " . $listing->id );


            } catch ( \Exception $e ) {
                $this->display( $e->getMessage() );
                $this->display( $e->getTraceAsString() );
            }

        }

        /*
        foreach ($listings->first()->locations as $location) {
            $this->updateLocationStats( $location );
            $this->display( "Location stats updated for " . $location->city );
        }
        */
    }

    public function getLocation( $city, $state, $country = "United States" )
    {

        //$this->setRandomProxy();
        $url = $this->formatUrl(self::LOCATION_URL, [
            'city' => $city,
            'state' => $state,
            'country' =>  $country
        ]);

        $results = $this->mapSearchResults( $this->decode( $this->get($url) ) );

        if ($results['count'] == 0) {
            return false;
        }

        $locationData = $results['location'];
        $location = $locationData->except('accuracy', 'result_type', 'place_id');

        if ( $location['city'] == null ) {
            $location['city'] = $city;
            $location['localized_city'] = $city;
            $location['address'] = $city;
        }

        if ( $location['state'] == null ) {
            $location['state'] = $state;
        }

        if ( $location['country'] == null ) {
            $location['country'] = $country;
        }

        $location['precision'] = 'city';
        $location['site_id'] = $locationData['place_id'];
        $location['source'] = self::SOURCE_IDENT;
        $location['localized_city'] = $location['city'];
        $location['address'] = $location['city'];
        $location['name'] = $location['city'] . "," . $location['state'] . "," . $location['country'];

        $this->display("Location ID found for {$city} (" . $locationData['place_id'] . ")");

        $newLocation = Location::firstOrNew([
            'city' => $location['city'],
            'state' => $location['state'],
            'country' => $location['country']
            ]
        );

        $newLocation->fill($location->toArray());
        $newLocation->save();

        $this->location = $newLocation;
        return $newLocation;
    }

    public function getAllListings($startPage = 1, $numPages = 1, $throttle = 1) {

        //$this->setRandomProxy();

        $page = $startPage;
        $numNoListings = 0;
        while ($page <= ($numPages + $startPage)) {
            $listings = $this->getListings($page);

            /* Exit if on last page and no more listings */
            if (!$listings) {
                $this->display("No more listings found on page " . $page);
                break;
            }

            $this->display("Found " . count($listings) . " on page " . $page);

            $results = $this->saveListings($listings);
            $this->display("Saved " . $results['new_listings'] .  " new listings, updated " . $results['existing_listings'] . " existing listings");

            if ($results['new_listings'] == 0) {
                    $numNoListings++;
            } else {
                $numNoListings = 0;
            }

            if ( $numNoListings >= 2 ) {
                $this->display( "Exiting on page {$page}, no new listings found." );
                break;
            }

            $page++;
            sleep($throttle);
        }
    }

    public function getListings($page = 1) {
        if (!$this->location instanceof Location) {
            throw new \Exception("Location must be set before listings can be fetched.");
        }

        $this->location->recordUpdate( 'listings' );

        $urlParams = $this->location->toArray();
        unset($urlParams['updates']);
        $urlParams['page'] = $page;
        $url = $this->formatUrl( self::LISTINGS_URL , $urlParams );
        $results = $this->mapSearchResults( $this->decode( $this->get( $url ) ) );

        if ($results['count'] == 0) {
            return false;
        }

        return collect($results['listings']);

    }

    public function getListingDetails(Listing $listing) {

        //$this->setRandomProxy();
        $url = $this->formatUrl(self::LISTING_DETAILS_URL, ['site_id' => $listing->site_id]);

        $results = $this->get($url);

        if (count($this->redirects) > 0) {
            $this->display("Listing is not currently active, aborting. \n");
            $listing->status = 'offline';
            $listing->save();
            return $listing;
        }

        if ($listing->status == 'offline') {
            $listing->status = "active";
            $listing->save();
        }

        $this->display("Fetching details for listing " . $listing->id);
        $this->display("Schedule last fetched " . $listing->schedule_updated_at);

        /* Get Listing Details */
        preg_match('/({"bootstrapData":.*?)-->/si', $results, $matches);
        if (!isset($matches[1]) || stristr($matches[1], "listing") === false) {
            $this->display("Could not find listing details.");
            return false;
        }
        /* Sample Key d306zoyjsyarp7ifhu67rjxn52tv0t20 */
        /* Get API Key, required for schedule and fee details */
        preg_match('/api&quot;,&quot;key&quot;:&quot;(.*?)&quot;/si', $results, $keyMatches);
        if ( !isset( $keyMatches[1] )) {
            $this->display( "Could not find API Key." );
            return false;
        }

        $this->apiKey = $keyMatches[1];
        $this->display("API Key found: " . $this->apiKey);

        $listingData = $this->decode($matches[1]);
        $listingData = $listingData['bootstrapData']['listing'];

        /* Update listing */
        if (isset( $listingData['price_interface']['cleaning_fee']['value'] )) {
            $listing->cleaning_rate = str_replace( "$", "", $listingData['price_interface']['cleaning_fee']['value'] );
        }

        if ( isset( $listingData['price_interface']['weekly_discount']['value'] ) ) {
            $listing->weekly_discount = str_replace( "%", "", $listingData['price_interface']['weekly_discount']['value'] );
        }

        if ( isset( $listingData['price_interface']['monthly_discount']['value'] ) ) {
            $listing->monthly_discount = str_replace( "%", "", $listingData['price_interface']['monthly_discount']['value'] );
        }

        if ( isset( $listingData['sectioned_description']['description'] ) ) {
           if (stristr( $listingData['sectioned_description']['description'], "condo") != false) {
               $listing->room_type = "condo";
           }
            if ( stristr( $listingData['sectioned_description']['description'], "apartment" ) != false ) {
                $listing->room_type = "apartment";
            }
        }

        if ( isset( $listingData['sectioned_description']['space'] ) ) {
            if ( stristr( $listingData['sectioned_description']['space'], "condo" ) != false ) {
                $listing->room_type = "condo";
            }
            if ( stristr( $listingData['sectioned_description']['space'], "apartment" ) != false ) {
                $listing->room_type = "apartment";
            }
        }

        if (isset($listingData['name'])) {
            $listing->name = trim( str_limit($listingData['name'], 150) );
        }

        if (stristr($listing->name, "condo") != false) {
            $listing->room_type = "condo";
        }

        if ( stristr( $listing->name, "apartment" ) != false ) {
            $listing->room_type = "apartment";
        }


        $listing->save();


        $ratesData = $this->getListingSchedule($listing);
        $this->display("Found " . $ratesData->count() . " days on schedule.");

        $savedRates = $this->saveRates($listing, $ratesData);
        $this->display("Saved " . count($savedRates['new_rates']) . " new rates, updated "
            . count( $savedRates['existing_rates'] ) . " existing rates, "
            . count( $savedRates['rate_changes'] ) . " rate changes. ");

        $this->display("Listing updated successfully.");

        return $listing;

    }

    public function hasInstantBook( Listing $listing )
    {

        //$this->setRandomProxy();
        $url = $this->formatUrl( self::LISTING_DETAILS_URL, [ 'site_id' => $listing->site_id ] );

        $results = $this->get( $url );

        $this->display( "Fetching details for listing " . $listing->id );
        $this->display( "Schedule last fetched " . $listing->schedule_updated_at );

        /* Get Listing Details */
        preg_match( '/<!--({"listing":.*?)-->/si', $results, $matches );
        if ( !isset( $matches[1] ) || stristr( $matches[1], "listing" ) === false ) {
            $this->display( "Could not find listing details." );
            return false;
        }
        /* Sample Key d306zoyjsyarp7ifhu67rjxn52tv0t20 */
        /* Get API Key, required for schedule and fee details */
        preg_match( '/api&quot;,&quot;key&quot;:&quot;(.*?)&quot;/si', $results, $keyMatches );
        if ( !isset( $keyMatches[1] ) ) {
            $this->display( "Could not find API Key." );
            return false;
        }

        $this->apiKey = $keyMatches[1];
        $this->display( "API Key found: " . $this->apiKey );

        $listingData = $this->decode( $matches[1] );
        $listingData = $listingData['listing'];

        if ($listingData['instant_bookable']) {
            return true;
        } else {
            return false;
        }


    }

    public function getListingPrices(Listing $listing) {

        if (empty($this->apiKey)) {
            throw new \Exception("API Key must be set before fetching pricing data.");
        }

        $pricingUrl = $this->formatUrl( self::LISTING_PRICE_URL, [ 'site_id' => $listing->site_id, 'api_key' => $this->apiKey ] );
        $pricingData = $this->decode( $this->get( $pricingUrl ) );
        return $pricingData;
    }

    public function getListingSchedule( Listing $listing, $numMonths = 8 )
    {

        if ( empty( $this->apiKey ) ) {
            throw new \Exception( "API Key must be set before fetching pricing data." );
        }

        $scheduleUrl = $this->formatUrl( self::LISTING_SCHEDULE_URL, [
            'site_id'     => $listing->site_id,
            'api_key'     => $this->apiKey,
            'start_month' => date( "n" ),
            'year'        => date( "Y" ),
            'num_months'  => $numMonths
        ] );

        $scheduleData = $this->decode( $this->get( $scheduleUrl ) );
        $days = collect([]);
        foreach ($scheduleData['calendar_months'] as $month) {
            foreach ($month['days'] as $day) {
                $days->push($day);
            }
        }

        return $days;
    }

    public function mapSearchResults($results) {

        try {
           $results = collect([
                'meta'       => collect( $results['results_json']['metadata'] ),
                'location'   => collect( $results['results_json']['metadata']['geography'] ),
                'listings'   => collect( $results['results_json']['search_results'] ),
                'pagination' => collect( $results['results_json']['metadata']['pagination'] ),
                'count'      => $results['results_json']['metadata']['listings_count']
            ]);
        } catch (\Exception $e) {
            $results = [
                'meta'       => collect(),
                'location'   => collect(),
                'listings'   => collect(),
                'pagination' => collect([ 'result_count' => 0 ]),
                'count'      => 0
            ];
        }

        return $results;
    }

    public function saveListings(Collection $listings) {
        $savedListings = collect([]);
        $newListings = 0;
        $existingListings = 0;
        foreach ($listings as $key => $item) {

            $listing = $item['listing'];

            $newListing = Listing::firstOrNew([
                'site_id' => $listing['id'],
                'source' => self::SOURCE_IDENT
            ]);

            if($newListing->exists) {
                $existingListings++;
            } else {
                $newListings++;
                $newListing->fill([
                    'schedule_updated_at' => date( "Y-m-d H:i:s", time() - 3600000 ),
                    'priority'            => 1
                ]);
            }

            $newListing->fill([
                'name'          => str_limit($listing['name'],150),
                'host_name'     => $listing['user']['first_name'],
                'city'          => $listing['localized_city'],
                'bedrooms'      => $listing['bedrooms'],
                'beds'          => $listing['beds'],
                'lat'           => $listing['lat'],
                'lng'           => $listing['lng'],
                'capacity'      => $listing['person_capacity'],
                'reviews_count' => $listing['reviews_count'],
                'rating'        => $listing['star_rating'],
                'room_type'     => $this->normaliseRoomType( $listing['room_type'] ),
                'rate_type'     => $item['pricing_quote']['rate_type'],
                'current_rate'  => floatval( $item['pricing_quote']['rate']['amount'] ),
            ]);


            $newListing->save();
            $this->location->listings()->syncWithoutDetaching([$newListing->id]);
            $savedListings->push($newListing);

        }

        return collect([
            'listings' => $savedListings,
            'new_listings' => $newListings,
            'existing_listings' => $existingListings
        ]);
    }

    public function saveRates(Listing $listing, Collection $rates) {

        $rateChanges = [];
        $newRates = [];
        $existingRates = [];
        foreach ($rates as $rate) {

            $existingRate = Rate::where("listing_id", $listing->id)->where("date", $rate['date'])->where( "current", true )->orderBy("id","desc")->first();
            $newRate = [
                'listing_id' => $listing->id,
                'rate'       => $rate['price']['local_price'],
                'date'       => $rate['date'],
                'available'  => $rate['available']
            ];

            /* If the rate/day already exists and the rate is different,
                we need to record a rate change and insert the new rate,
                otherwise we can just update the existing rate.
            */
            if ( $existingRate instanceof Rate ) {

                /* Record a rate change if price or availablity has changed */
                if ( $existingRate->rate != $rate['price']['local_price'] || $existingRate->available != $rate['available']) {
                    /* Save new rate */
                    $rateModel = Rate::create( $newRate );

                    /* Create & record the rate change */
                    $rateChange = RateChange::create( [
                        'listing_id'   => $listing->id,
                        'prev_rate_id' => $existingRate->id,
                        'new_rate_id'  => $rateModel->id
                    ] );

                    /* Set old record 'current' status to false */
                    $existingRate->update(['current' => false]);

                    $newRates[] = $rateModel;
                    $rateChanges[] = $rateChange;

                /* Update existing rate record */
                } else {
                    $existingRate->fill( $newRate );
                    $existingRate->save();
                    $existingRates[] = $existingRates;
                }


            /* Create a new rate entry if none exist */
            } else {
                $rateModel = Rate::create( $newRate );
                $newRates[] = $rateModel;
            }

        }

        return [
            'new_rates' => collect($newRates),
            'existing_rates' => collect($existingRates),
            'rate_changes' => collect($rateChanges)
        ];

    }

    public function saveBookings( Listing $listing )
    {

        /* Fetch all rates from this day forward */
        $rates = $listing->rates()->whereDate('date','>=',date("Y-m-d"))->get();
        $lastBooking = $listing->bookings()->orderBy("id", "desc")->first();

        $bookings = [
            'listing_id' => $listing->id,
            'total_days' => $rates->count(),
            'booked_days' => 0,
            'available_days' => 0,
            'new_bookings' => 0,
            'avg_rate' => 0
        ];

        $totalRate = 0;

        /* Summarize bookings */
        foreach ($rates as $rate) {

            if ($rate->available) {
                $bookings['available_days']++;
             } else {
                $bookings['booked_days']++;
            }

            $totalRate += $rate->rate;

        }

        $bookings['new_bookings'] = max(0, $bookings['booked_days'] - $lastBooking['booked_days']);
        $bookings['avg_rate'] = round($totalRate / max(1, $rates->count()), 2);

        $newBooking = Booking::create($bookings);

        return $newBooking;



    }

    public function updateLocationStats( Location $location) {
        $location->load("listings");

        if ($location->listings()->count() == 0) {
            return true;
        }

        $updateParams = [
            'avg_bedrooms' => $location->avg_bedrooms,
            'avg_bathrooms' => $location->avg_bathrooms,
            'avg_beds' => $location->avg_beds,
            'avg_price' => $location->avg_price_home,
            'avg_price_apt' => $location->avg_price_apt,
            'avg_price_room' => $location->avg_price_room,
            'avg_price_shared' => $location->avg_price_shared,
            'num_listings' => $location->listings()->count()
        ];

        $stats = [
            'bedrooms'     => 0,
            'bathrooms'    => 0,
            'beds'         => 0,
            'price'        => 0,
            'price_home'   => 0,
            'price_apt'    => 0,
            'price_room'   => 0,
            'price_shared' => 0,
        ];

        foreach ($location->listings as $listing) {
            $roomKey  = "home";
            if ($listing->room_type == 'apartment' || $listing->room_type == 'condo') {
                $roomKey = 'apt';
            }
            $stats['price'] += $listing->current_rate;
            $stats['price_' . $roomKey] += $listing->current_rate;
            $stats['beds'] += $listing->beds;
            $stats['bathrooms'] += $listing->bathrooms;
            $stats['bedrooms'] += $listing->bedrooms;
        }

        $updateParams['avg_bedrooms'] = round($stats['bedrooms'] / $updateParams['num_listings'], 2);
        $updateParams['avg_bathrooms'] = round($stats['bathrooms'] / $updateParams['num_listings'], 2);
        $updateParams['avg_beds'] = round($stats['beds'] / $updateParams['num_listings'], 2);
        $updateParams['avg_price'] = round($stats['price'] / $updateParams['num_listings'], 2);
        $updateParams['avg_price_room'] = round($stats['price_room'] / $updateParams['num_listings'], 2);
        $updateParams['avg_price_shared'] = round($stats['price_shared'] / $updateParams['num_listings'], 2);
        $updateParams['avg_price_apt'] = round($stats['price_apt'] / $updateParams['num_listings'], 2);
        $updateParams['avg_price_home'] = round($stats['price_home'] / $updateParams['num_listings'], 2);
        $updateParams['profit_score'] = round($location->listings->avg('profit_score'), 2);

        /* Update Primary Stats */
        $location->update($updateParams);

        /* Update Secondary Stats */
        $location->updateStats();

        return true;
    }

    public function normaliseRoomType($roomType) {
        switch ($roomType) {
            case 'Entire home/apt':
                return 'home';
            case 'Private Room':
                return 'room';
            case 'Shared Room':
                return 'shared';
            default:
                return 'home';
        }
    }


}
