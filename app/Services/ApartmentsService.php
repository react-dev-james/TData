<?php

namespace App\Services;

use \App\Location;
use \App\Property;

use Illuminate\Support\Collection;
use Symfony\Component\DomCrawler\Crawler;

class ApartmentsService extends ScraperService
{

    /**
     * @var \App\Location
     */
    protected $location;
    protected $apiKey;

    const SOURCE_IDENT = "apartments";
    const API_KEY = '';
    CONST SUGGEST_URL = 'https://www.apartments.com/services/geography/search/';
    const LISTINGS_URL = 'https://www.apartments.com/{regionSlug}/{page}/';

    public function __construct( Array $clientOptions = [ 'verify' => false ] )
    {
        $this->useCrawlera();
        parent::__construct( $clientOptions );
    }

    protected function xmlDecode( $response )
    {
        $xml = simplexml_load_string( $response );
        $json = @json_encode( $xml );
        return @json_decode( $json, true );
    }

    public function setLocation( Location $location )
    {
        $this->location = $location;
        return $this;
    }

    public function getListings( $page = 1, $maxPages = 5, $delay = 0, $useCountry = false )
    {
        if ( !$this->location instanceof Location ) {
            throw new \Exception( "Location must be set before properties can be fetched." );
        }

        $regionSlugs = $this->getRegion( $this->location->city, $this->location->state, $this->location->country, $useCountry );
        $regionCount = count( $regionSlugs);
        $this->display( "Found {$regionCount} matching regions for {$this->location->city}." );

        $updatedListings = collect();
        foreach ($regionSlugs as $regionSlug) {
            $this->display( "Found region slug ({$regionSlug}) for Apartments.com, fetching properties." );

            $curPage = max( 0, $page - 1 );
            $listings = collect();
            $totalMax = $maxPages + $curPage;
            while ($curPage <= ( $totalMax )) {
                $curPage++;
                try {
                    $results = $this->get( $this->formatUrl( self::LISTINGS_URL, [
                        'regionSlug' => $regionSlug,
                        'page'       => $curPage
                    ] ) );
                } catch (\Exception $e) {
                    $this->display("Error fetching page for region: {$regionSlug}. ");
                    break;
                }


                // $this->save( 'AparmentListingResultsPage' . $curPage . ".html", $results );

                /* Parse listing data */
                $parser = new Crawler( $results );
                if ( $parser->filter( 'article[class*="placard"]' )->count() <= 0 ) {
                    $this->display( "No more listings found on page " . $curPage );
                    break;
                }

                /* Parse and normalize listings */
                $parser->filter( 'article[class*="placard"]' )->each( function ( Crawler $item, $i ) use ( $listings ) {

                    if (empty($item->attr("data-listingid")) ) {
                        return;
                    }

                    $newListing = [];
                    try {
                        $newListing['source'] = 'apartments';
                        $newListing['city'] = $this->location->city;
                        $listingUrl = str_replace("https://www.apartments.com","", $item->attr( "data-url" ));
                        $newListing['site_id'] = $listingUrl ? $listingUrl : $item->attr("data-listingid");
                        $newListing['listing_id'] = $item->attr("data-listingid");
                        $newListing['url'] = $item->attr( "data-url" );
                    } catch ( \Exception $e ) {
                        return;
                    }

                    /* Don't save listings without url or site id */
                    if ( empty( $newListing['url'] ) || empty( $newListing['site_id'] )) {
                        return;
                    }

                    $listings->push( $newListing );

                } );

                if ( $delay > 0 ) {
                    sleep( $delay );
                }
            }

            $this->display("Found {$listings->count()} listings for {$this->location->city}. Fetching details.");
            $updatedListings = collect();
            foreach ($listings as $listing) {
                $listingDetails = $this->getListingDetails($listing);

                if ($listingDetails == false) {
                    continue;
                }

                $updatedListings->push($listingDetails);
            }
            $this->saveListings($updatedListings);
        }

        return $updatedListings;
    }

    public function getListingDetails( Array $listing )
    {
        if (!isset($listing['url']) || empty($listing['url'])) {
            $this->display( "Could not fetch listing details, invalid URL supplied" );
            return false;
        }

        $results = $this->get($listing['url']);
        $source = $results;
        if (stristr($results, "startup.init") === false) {
            $this->display("Could not fetch listing details, no init JSON detected.");
            return false;
        }

        preg_match_all( "/startup.init\((\{.*?\})\);/si", $results, $matches );
        if (!isset($matches[1][0])) {
            $this->display( "Could not fetch listing details, no Regex match." );
            return false;
        }

        // $this->display("Fetching id: {$listing['listing_id']} from {$listing['url']} ");

        // $this->save( 'AparmentListingDetails.json', $matches[1][0] );
        /* Clean up the JSON to make it valid, replacing all quotes where necessary */
        $results = preg_replace( '/("(.*?)"|(\w+))(\s*:\s*(".*?"|.))/s', '"$2$3"$4', $matches[1][0] );
        $results =  preg_replace( "/:\s'(.*)'/", ": \"$1\"", $results);
        //$this->save( 'AparmentListingDetailsFixed2.json', $results );

        $details = @json_decode($results, true);

        /* Make sure the parsed listing matches the provided listing ID */
        try {
            if ( !isset( $details['listingId'] ) || trim( $listing['listing_id'] ) != ( $details['listingId'] ) ) {
                //$this->display( "Could not fetch listing details, listing ID's do not match ({$details['listingId']} and {$listing['listing_id']}) ." );
                return false;
            }
        } catch(\Exception $e) {
            return false;
        }


        /* Unset extra fields to prep for merging & saving */
        unset($listing['url']);
        unset($listing['listing_id']);
        $listing = array_merge($listing, [
            'phone' => $details['phoneNumber'],
            'address' => $details['listingAddress'],
            'lat' => $details['location']['latitude'],
            'lng' => $details['location']['longitude'],
            'name' => $details['listingName'],
        ]);

        /* Set a default name if listing does not have an assigned name */
        if (empty($listing['name'])) {
            $listing['name'] = $listing['address'] . ", " . $listing['city'];
        }

        /* Organize all the different rental options */
        $rentals = collect();
        foreach ($details['rentals'] as $rental) {
            $rentals->push([
                'key'   => $rental['RentalKey'],
                'price' => str_replace("$","", $rental['RentDisplay']),
                'beds' => $rental['Beds'],
                'bathrooms' => $rental['Baths'],
                'sq_ft' => str_replace(["Sq Ft",","],"", $rental['SquareFootDisplay'])
            ]);
        }


        if ($rentals->count() == 0) {
            /**
             * For some reason Apartments.com doesn't always include all rentals in the JSON
             * Try parsing rentals from the page source itself
             */
            // $this->display('No rentals found, trying page source.');
            $parser = new Crawler( $source );
            try {
                $parser->filter( 'tr[class*="rentalGridRow"]' )->each( function ( Crawler $item, $i ) use ( $rentals ) {
                    $newRental = [
                        'key'       => str_random( 6 ),
                        'price'     => str_replace( "$", "", $item->attr( 'data-maxrent' ) ),
                        'beds'      => $item->attr( 'data-beds' ),
                        'bathrooms' => round( $item->attr( 'data-baths' ) ),
                        'sq_ft'     => str_replace( [ "Sq Ft", "," ], "", $item->filter( 'td[class="sqft"]' )->text() )
                    ];
                    $rentals->push( $newRental );
                } );
            } catch (\Exception $e) {
                $this->display($e->getMessage());
            }

        }

        if ( $rentals->count() == 0 ) {
            return false;
        }

        $listing['rentals'] = $rentals;
        return $listing;

    }

    public function saveListings( Collection $listings )
    {
        $savedListings = collect( [] );
        $newListings = 0;
        $existingListings = 0;
        foreach ($listings as $listing) {

            if ($listing['rentals']->count() == 0) {
                $this->display("No rentas found for listing, skipping.");
                continue;
            }

            /* Each rental is a potential listing */
            $rentalNum = 0;
            foreach ($listing['rentals'] as $rental) {

                /* Skip rentals with invalid values */
                if (!is_int(intval($rental['price']))
                    || intval( $rental['price'])== 0
                    || $rental['price'] == 'Call for Rent'
                    || !is_int( intval($rental['beds']))
                    || !is_int( intval($rental['bathrooms']))
                    || !is_int( intval($rental['sq_ft']))
                ) {
                    continue;
                }

                $rentalNum++;

                //$this->display("Valid rental found for listing, saving new listing.");

                /* Merge the rental with the listing data and add a unique rental number identifer for the site ID */
                $mergedListing = $listing;
                unset($mergedListing['rentals']);
                $mergedListing['site_id'] .= "?rn=" . $rentalNum;
                $mergedListing['price'] = $rental['price'];
                $mergedListing['bedrooms'] = !empty( $rental['beds'] ) ? $rental['beds'] : 1;
                $mergedListing['bathrooms'] = !empty( $rental['bathrooms'] ) ? $rental['bathrooms'] : 1;
                $mergedListing['sq_ft'] = !empty($rental['sq_ft']) ? $rental['sq_ft'] : 0;

                /* Clean up the listing price */
                $mergedListing['price'] = str_replace(",","", $mergedListing['price']);
                if (stristr($mergedListing['price'],"-") !== false) {
                    $prices = explode("-", $mergedListing['price']);
                    $collectedPrices = collect();
                    foreach ($prices as $price) {
                        $collectedPrices->push( ['price' => intval(trim($price))] );
                    }
                    $mergedListing['price'] = round($collectedPrices->avg('price'));
                }

                /* Clean up the listing square footage */
                $mergedListing['sq_ft'] = str_replace( ",", "", $mergedListing['sq_ft'] );
                if ( stristr( $mergedListing['sq_ft'], "-" ) !== false ) {
                    $squareFootages = explode( "-", $mergedListing['sq_ft'] );
                    $collectedSquareFootages = collect();
                    foreach ($squareFootages as $squareFootage) {
                        $collectedSquareFootages->push( [ 'sq_ft' => intval( trim( $squareFootage ) ) ] );
                    }
                    $mergedListing['sq_ft'] = round( $collectedSquareFootages->avg( 'sq_ft' ) );
                }                

                $newListing = Property::firstOrNew( [
                    'site_id' => $mergedListing['site_id'],
                    'source'  => self::SOURCE_IDENT
                ] );

                $listingExists = false;
                if ( $newListing->exists ) {
                    $listingExists = true;
                    $existingListings++;
                } else {
                    $newListings++;
                }

                $newListing->fill( $mergedListing );

                try{
                    $newListing->save();
                    if ( !$listingExists ) {
                        $this->location->properties()->attach( $newListing->id );
                    }
                    $savedListings->push( $newListing );
                } catch (\Exception $e) {
                    $this->display("Error saving listing: " . $e->getMessage());
                }
            }
        }

        $this->display( "Saved " . $newListings . " new apartments, updated " . $existingListings . " existing apartments." );

        return collect( [
            'listings'          => $savedListings,
            'new_listings'      => $newListings,
            'existing_listings' => $existingListings
        ] );
    }

    /**
     * Find the matching region listing for a given state, city & country
     * @param $city
     * @param $state
     * @param string $country
     * @param bool $useCountry
     * @return bool|mixed
     * @throws \Exception
     */
    public function getRegion( $city, $state, $country = 'United States', $useCountry = false )
    {

        /* Don't pass the country to the auto suggest for US based regions */
        if ( !$useCountry || $country == 'United States' ) {
            $country = '';
        }

        $results = $this->decode(
            $this->post(
                self::SUGGEST_URL,
                @json_encode( [ 't' => $city . "," . $state ] ),
                [ 'Content-Type' => 'application/json' ]
            )
        );

        if ( !is_array( $results ) || count( $results ) == 0 ) {
            throw new \Exception( "Could not find region ID for " . $city . "," . $state . "," . $country );
        }

        /* Use the first result with matching city as the best region match */
        $matchingRegions = [];
        foreach ($results as $region) {
            if ( isset( $region['Address'] ) && isset($region['Address']['City']) && $region['Address']['City'] == $city ) {
                $matchingRegions[] = $region;
            }
        }

        /* Special handling for parks/rec regions */
        $regionSlugs = [];
        foreach ($matchingRegions as $matchingRegion) {
            switch ($matchingRegion['GeographyType']) {
                /* Parks and rec regions */
                case 18:
                    $regionSlugs[] = "parks-and-recreation/" .
                        str_slug( strtolower( $matchingRegion['Address']['State'] ), "-" ) . "/" .
                        str_slug( strtolower( $matchingRegion['Address']['City'] ), "-" ) . "/" .
                        str_slug( strtolower( $matchingRegion['Address']['Title'] ), "-" ) . "/" .
                        strtolower( $matchingRegion['ID'] );

                    break;
                default:
                    $regionSlugs[] = str_slug( $matchingRegion['Display'], '-' );
                    break;
            }
        }

        return $regionSlugs;

    }

}
