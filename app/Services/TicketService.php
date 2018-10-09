<?php

namespace App\Services;

use \App\Listing;
use Illuminate\Support\Collection;
use Symfony\Component\DomCrawler\Crawler;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;


class TicketService extends ScraperService implements IScraper
{

    const BOX_LOGIN_URL = 'https://www.boxofficefox.com/login/';
    const BOX_ACCOUNT_URL = 'https://www.boxofficefox.com/myaccount/';
    const BOX_LOGIN_POST_URL = 'https://www.boxofficefox.com/wp-admin/admin-ajax.php';
    const BOX_SEARCH_URL = 'https://www.boxofficefox.com/wp-admin/admin-ajax.php';
    const BOX_USERNAME = 'mjseats@gmail.com';
    const BOX_PASSWORD = 'noandrew';

    const TD_LOGIN_URL = 'http://broker.ticketdata.com/cgi-bin/userbase.cgi?action=validate';
    const TD_USERNAME = 'nick.stagefront';
    const TD_PASSWORD = 'Columbia150';
    const TD_CATEGORY_URL = 'http://broker.ticketdata.com/?categories';
    const TD_SEARCH_URL = 'http://broker.ticketdata.com/';

    /**
     * @var \App\Location
     */
    protected $location;
    protected $apiKey;
    protected $options;
    protected $state;
    protected $dateHash;

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

    public function boxOfficeSearchParams(Carbon $startDate, Carbon $endDate, $offset = 0, $limit = 500) {

        $startDateString = $startDate->format("m/d/y");
        $endDateString = $endDate->format("m/d/y");

        $params = [
            "sEcho"          => "1",
            "iColumns"       => "9",
            "sColumns"       => "",
            "iDisplayStart"  => $offset,
            "iDisplayLength" => $limit,
            "mDataProp_0"    => "ticket_sale_start",
            "mDataProp_1"    => "event_start_time",
            "mDataProp_2"    => "event_name",
            "mDataProp_3"    => "venue_name",
            "mDataProp_4"    => "venue_capacity",
            "mDataProp_5"    => "price_col",
            "mDataProp_6"    => "venue_city",
            "mDataProp_7"    => "venue_state",
            "mDataProp_8"    => "report_link",
            "sSearch"        => "",
            "bRegex"         => "false",
            "sSearch_0"      => "",
            "bRegex_0"       => "false",
            "bSearchable_0"  => "true",
            "sSearch_1"      => "",
            "bRegex_1"       => "false",
            "bSearchable_1"  => "true",
            "sSearch_2"      => "",
            "bRegex_2"       => "false",
            "bSearchable_2"  => "true",
            "sSearch_3"      => "",
            "bRegex_3"       => "false",
            "bSearchable_3"  => "true",
            "sSearch_4"      => "",
            "bRegex_4"       => "false",
            "bSearchable_4"  => "true",
            "sSearch_5"      => "",
            "bRegex_5"       => "false",
            "bSearchable_5"  => "true",
            "sSearch_6"      => "",
            "bRegex_6"       => "false",
            "bSearchable_6"  => "true",
            "sSearch_7"      => "",
            "bRegex_7"       => "false",
            "bSearchable_7"  => "true",
            "sSearch_8"      => "",
            "bRegex_8"       => "false",
            "bSearchable_8"  => "true",
            "iSortCol_0"     => "0",
            "sSortDir_0"     => "asc",
            "iSortingCols"   => "1",
            "bSortable_0"    => "true",
            "bSortable_1"    => "true",
            "bSortable_2"    => "true",
            "bSortable_3"    => "true",
            "bSortable_4"    => "true",
            "bSortable_5"    => "true",
            "bSortable_6"    => "true",
            "bSortable_7"    => "true",
            "bSortable_8"    => "true",
            "action"         => "bofv2",
            "data"           => "onsale_range=" . $startDateString . "+-+" . $endDateString . "&event_range=&category=All&type=all",
        ];

        return $params;
    }

    public function fetchBoxOfficeListings($limit = 500, $maxPages = 1)
    {

        if (!$this->state('box_logged_in')) {
            if (!$this->boxOfficeLogin()) {
                Log::Error( "Error logging into box office." );
                return false;
            }
        }

        $startDate = Carbon::now();
        $startDate->startOfWeek();
        $endDate = $startDate->copy()->addDays(7);
        $this->dateHash = md5($startDate->timestamp . $endDate->timestamp);
        $start = 0;
        $offset = $limit;
        $savedListings = collect();

        for ($i = 0; $i <= $maxPages; $i++) {
            $start = $offset * $i;
            echo "Fetching page " . $i . " from box office with start of " . $start . "\n";
            Log::info("Fetching page " . $i . " from box office with start of " . $start);

            $params = $this->boxOfficeSearchParams( $startDate, $endDate, $start, $limit );
            $results = $this->post( self::BOX_SEARCH_URL, $params );
            $results = @json_decode( $results, true );

            if ( $i == 0 && isset( $results['iTotalDisplayRecords']) ) {
                echo "Found " . $results['iTotalDisplayRecords'] . " from box office fox, starting parsing.\n";
                Log::info("Found " . $results['iTotalDisplayRecords'] . " from box office fox, starting parsing.");
            }

            if ( !isset( $results['iTotalDisplayRecords'] ) || $results['iTotalDisplayRecords'] <= 0 || $results['iTotalDisplayRecords'] <= ( $start + $offset ) ) {
                echo "No more records found from box office fox.\n";
                Log::info("No more records found from box office fox.");
                return false;
            }

            /* Parse and normalize returned results */
            $records = $this->boxOfficeNormalize($results['aaData']);

            $savedListings->push($this->saveListings($records));

        }

        return $savedListings;
    }

    public function boxOfficeNormalize( Array $results )
    {
        $records = collect();
        foreach ($results as $result) {
            $newRecord = [
                'source'         => 'boxofficefox',
                'category'       => ucfirst( $result['category_name'] ),
                'event_name'     => $result['event_name'],
                'slug'           => str_slug( $result['event_name'] ),
                'recurring'      => (bool) $result['recurring'],
                'offer_code'     => $result['ticket_offer_code'],
                'ticket_url'     => $result['buy_ticket_url'],
                'venue'          => $result['venue_name'],
                'venue_zip'      => $result['venue_zip'],
                'venue_city'     => $result['venue_city'],
                'venue_state'    => $result['venue_state'],
                'venue_country'  => $result['venue_country'],
                'venue_lat'      => $result['venue_lat'],
                'venue_lng'      => $result['venue_long'],
                'venue_capacity' => intval(str_replace(",","",$result['venue_capacity'])),
                'event_date'     => date( "Y-m-d H:i:s", $result['event_start_time_local'] ),
                'event_day'      => date( "l", $result['event_start_time_local'] ),
                'date_hash'      => $this->dateHash
            ];

            if (stristr($result['price_col'], "-") !== false) {
                list($lowPrice, $highPrice) = explode("-", $result['price_col']);
            } else {
                $lowPrice = $result['price_col'];
                $highPrice = $result['price_col'];
            }

            $lowPrice = str_replace(",", "", $lowPrice);
            $highPrice = str_replace(",", "", $highPrice);
            $lowPrice = intval(str_replace("$", "", $lowPrice));
            $highPrice = intval(str_replace("$", "", $highPrice));

            $newRecord['low_ticket_price'] = $lowPrice;
            $newRecord['high_ticket_price'] = $highPrice;
            $newRecord['avg_ticket_price'] = round(($highPrice + $lowPrice) / 2);

            /* debug pricing issue */
            //if( $lowPrice <= 0 || $highPrice <= 0 ) {
            //    Log::info('/***** box office listing price problem ***/');
            //    Log::info('-- event name: ' . $result['event_name']);
            //    Log::info($newRecord);
            //}


            /* Extract ticket sale data */
            $parser = new Crawler($result['ticket_sale_start']);
            $saleData = $parser->filter('span')->first()->text();
            if ($saleData == 'OnSale' || $saleData == 'OnPresale') {
                $newRecord['sale_status'] = $saleData;
                $newRecord['sale'] = [
                    'sale_date' => date( "Y-m-d 09:00:00" ),
                    'day'       => date( "l" ),
                    'manual'    => true,
                    'type'      => 'current',
                    'offer_code' => $result['ticket_offer_code'],
                    'is_future' => false
                ];
            } else {

                /**
                 * Parse the data from the sale status, format is: 09.16.17 1pm
                 */
                $saleDate = str_replace(".","/", $saleData);
                $newRecord['sale_status'] = "Future";
                $newRecord['sale'] = [
                    'sale_date' => date( "Y-m-d H:i:s", strtotime( $saleDate ) ),
                    'day'       => date( "l", strtotime( $saleDate ) ),
                    'manual'    => false,
                    'type'      => 'future',
                    'offer_code' => '',
                    'is_future'  => true
                ];
            }

            $records->push($newRecord);
        }

        return $records;
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
        dd($response);
        if ($response->type == 'success') {
            $this->state['box_logged_in'] = true;
            $this->display( "Logged into box office fox successfully." );
            return true;
        }

        return false;

    }

    public function ticketDataLogin(  )
    {

        /**
         * userbase_username:nick.stagefront
         * userbase_password:Columbia150
         * remember_me:on
         * ref:
         * loginreturn:
         */

        $loginParams = [
            'userbase_username' => self::TD_USERNAME,
            'userbase_password' => self::TD_PASSWORD,
            'remember_me' => 'on',
            'ref' => '',
            'loginreturn' => ''
        ];

        /* Check if we are already logged in */
        $results = $this->get(self::TD_CATEGORY_URL);
        $this->save('ticketdata_category_url.html');
        if (stristr($results, 'Logout') != false) {
            $this->display("Logged into ticket data using cookies. ");
            $this->state['ticket_logged_in'] = true;
            return true;
        }

        $results = $this->post(self::TD_LOGIN_URL, $loginParams);
        $this->save( 'ticketdata_login_url.html' );
        if ( stristr( $results, 'Logout' ) != false ) {
            $this->state['ticket_logged_in'] = true;
            $this->display( "Logged into ticket data using username/password. " );
            return true;
        }

        $this->display( "Error logging into to ticket data " );
        return false;

    }

    public function ticketDataParams($category, $subCategory = '')
    {
        $params = [
            "hier_start"          => $category,
            "hier_A"              => $subCategory,
            "hier_B"              => "0",
            "hier_C"              => "0",
            "hier_D"              => "0",
            "upcoming_only"       => "1",
            "show_as_events_type" => "upcoming",
            "show_as"             => "categories",
            "ajax"                => "1",
            "categories"          => "1",
            "FAVORITING"          => "1"
        ];

        return $params;
    }

    public function fetchTicketDataListings( Array $categories = [] )
    {

            if ( !$this->state( 'ticket_logged_in' ) ) {
                if ( !$this->ticketDataLogin() ) {
                    return false;
                }
            }

            $savedListings = collect();

            $params = $this->ticketDataParams('Theater tickets and Arts tickets', 'Dance');
            $results = $this->get( self::TD_SEARCH_URL, $params );
            $this->save('ticketdata_search.html');
            $crawler = new Crawler($results);
            $numResults = $crawler->filter('tr')->count();

            if (stristr($results, 'search_results') === false) {
                $this->display("Error loading search results.");
                return false;
            }

            $this->display("Found " .$numResults . " results for category search.");

            /* Parse and normalize returned results */
            $records = $this->ticketDataNormalize( $crawler, $results );

            //$savedListings->push( $this->saveListings( $records ) );

    }

    public function ticketDataNormalize(Crawler $crawler, $results )
    {
        $records = collect();
        $rowIndex = 0;
        $crawler->filter( 'tr' )->each(function(Crawler $row) use (&$records, &$rowIndex) {
            if ($rowIndex == 0) {
                $rowIndex++;
                return;
            }

            $rowIndex++;


            $newRecord = [
                'source'         => 'ticketdata',
                'category'       => $row->filter('td' )->eq( 3 )->text(),
                'slug'           => str_slug( $row->filter( 'td' )->eq( 0 )->text() ),
            ];

            dd($newRecord);
        });
    }

    public function saveListings(Collection $listings) {

        $newListings = 0;
        $updatedListings = 0;
        $newSales = 0;
        $updatedSales = 0;
        foreach ($listings as $listing) {

            $sale = $listing['sale'];
            unset($listing['sale']);

            // set on sale date here
            $listing['first_onsale_date'] = $sale['sale_date'];

            $newListing = Listing::firstOrCreate([
                'slug' => $listing['slug'],
                'venue' => $listing['venue'],
                'source' => $listing['source']
            ], $listing);

            //$newListing = Listing::create($listing);

            if ($newListing->wasRecentlyCreated === true) {
                $newListings++;
            } else {
                $newListing->fill($listing)->save();
                $updatedListings++;
            }

            $newSale = \App\Sale::firstOrCreate([
                'listing_id' => $newListing->id,
                'sale_date'  => $sale['sale_date'],
                'type'       => $sale['type']
            ], $sale);

            if ( $newSale->wasRecentlyCreated === true ) {
                $newSales++;
            } else {
                $newSale->fill( $sale )->save();
                $updatedSales++;
            }

            /* update venues_bof for capacity */

        }

        return collect([
            'new' => $newListings,
            'updated' => $updatedListings,
            'sales_new' => $newSales,
            'sales_updated' => $updatedSales
        ]);
    }




}
