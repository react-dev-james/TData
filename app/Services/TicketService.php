<?php

namespace App\Services;

use \App\Listing;
use Illuminate\Support\Collection;
use Symfony\Component\DomCrawler\Crawler;


class TicketService extends ScraperService implements IScraper
{

    const BOX_LOGIN_URL = 'https://www.boxofficefox.com/login/';
    const BOX_ACCOUNT_URL = 'https://www.boxofficefox.com/myaccount/';
    const BOX_LOGIN_POST_URL = 'https://www.boxofficefox.com/wp-admin/admin-ajax.php';
    const BOX_USERNAME = 'mjseats@gmail.com';
    const BOX_PASSWORD = 'noandrew';

    /**
     * @var \App\Location
     */
    protected $location;
    protected $apiKey;
    protected $options;
    protected $state;

    public function execute(Array $options  )
    {
        $this->options = $options;
        $this->fetchBoxOfficeListings();
        $this->state = [];
    }

    public function state($key)
    {
        if (!isset($this->state[$key])) {
            return false;
        }

        return $this->state[$key];
    }

    public function fetchBoxOfficeListings(  )
    {
        if (!$this->state('box_logged_in')) {
            $this->boxOfficeLogin();
        }

        if ( !$this->state( 'box_logged_in' ) ) {
            $this->display("Error logging into box office.");
        }

        $result = $this->get( self::BOX_ACCOUNT_URL );
        $this->save( 'box_office_account.html' );
    }

    public function boxOfficeLogin(  )
    {
        /* Get the security key to login */
        $results = $this->get(self::BOX_LOGIN_URL);
        $this->save('box_office_pre_login.html');

        /* Check if we are already logged in */
        if (stristr($results, "My Account") !== false && stristr( $results, "Log Out" ) !== false) {
            $this->state['box_logged_in'] = true;
            $this->display( "Logged into box office fox using existing cookies." );
            return true;
        }

        $parser = new Crawler($results);
        if ($parser->filter('input[name="mm-security"]')->count() <= 0) {
            $this->display("Unable to fetch login security code.");
            return false;
        }

        $code = $parser->filter( 'input[name="mm-security"]' )->attr("value");
        if (empty($code)) {
            $this->display( "Unable to fetch login security code." );
            return false;
        }

        $this->display( "Found security code: " . $code );

        $postParams = [
            'mm-security' => $code,
            'mm_action' => 'login',
            'log' => self::BOX_USERNAME,
            'pwd' => self::BOX_PASSWORD,
            'rememberme' => 'true',
            'referer' => '/login/',
            'method' => 'performAction',
            'action' => 'module-handle',
            'module' => 'MM_LoginFormView'
        ];

        $results = $this->post(self::BOX_LOGIN_POST_URL, $postParams);
        $this->save( 'box_office_post_login.html' );

        $response = @json_encode($results);
        if ($response->type == 'success') {
            $this->state['box_logged_in'] = true;
            $this->display( "Logged into box office fox successfully." );
            return true;
        }

        return false;

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




}
