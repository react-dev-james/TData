<?php

namespace App\Http\Controllers\Api;

use App\Event;
use App\ListingsView;
use App\Reference;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;
use App\Http\Controllers\Controller;
use App\User;
use App\Listing;
use App\EventState;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ListingsTicketMasterController extends Controller
{

    public function __construct()
    {
        $this->middleware( "auth" )->except("fetch");
    }

    /**
     * Return listings for admin listings management.
     * Supported filters: filter-active
     * @param Request $request ['filter','search','sort','pager','page']
     * @return mixed
     */
    public function index( Request $request )
    {

        if ($request->get('currentFilter','true') == 'true') {
            // get dates
            $start_date = Carbon::now()->startOfWeek();
            $end_date = Carbon::now()->endOfWeek();

            $query = DB::table('listings_view')
                ->where('first_onsale_datetime', '>=', $start_date)
                ->where('first_onsale_datetime', '<=', $end_date);
        } else {
            $query = DB::table('listings_view');
        }

        /* Handle custom sorting on relations */
        if ( $request->has( "sort" ) && !$request->has( 'multiSort' ) ) {
            $query = $this->sort( $query, $request->sort['name'], $request->sort['type'] );
        }

        /* Handle multiple sorting fields */
        if ( $request->has( 'multiSort' ) ) {
            foreach ($request->multiSort as $sortOptions) {
                $query = $this->sort( $query, $sortOptions['field'], $sortOptions['direction'] );
            }
        }

        /* Default sorting */
        if ( !$request->has( 'sort' ) && !$request->has( 'multiSort' ) ) {
            $query = $this->sort( $query );
        }

        if ( $request->has( "pager" ) ) {
            // for some reason, this was not getting set in some cases
            if( isset($request->pager['page'])) {
                $page = $request->pager['page'];
            }
            $size = $request->pager['size'];
        } else {
            $page = 1;
            $size = 25;
        }

        // -- todo -- verify other search fields
        if ( $request->has( "search" ) && !empty( $request->search ) ) {
            $searchField = $request->get( 'searchField', 'all' );
            if ( $searchField == 'all' ) {
                $query->where( "event_name", "ilike", "%" . $request->search . "%" );
                $query->orWhere( "venue_name", "ilike", "%" . $request->search . "%" );
                $query->orWhere( "venue_city", "ilike", "%" . $request->search . "%" );
            } else if ( !empty( $searchField ) ) {
                    $query->where( $searchField, "ilike", "%" . $request->search . "%" );
            }

        }

        /* Date filters */
        if ( $request->has( "dateFilter" ) && !empty( $request->dateFilter ) ) {
            switch ($request->dateFilter) {
                case "monday":
                    $query->whereRaw( 'EXTRACT(dow FROM listings_view.first_onsale_datetime) = 1' );
                    /*
                    $query->join( 'sales as salesDate', function ( $join ) {
                        $join->on( 'salesDate.listing_id', '=', 'listings_view.id' )->where( 'salesDate.day', 'Monday' )->limit( 1 );
                    } )
                        ->whereNotNull( "salesDate.sale_date" )
                        ->select( "listings_view.*" );
                    */
                    break;
                case "tuesday":
                    $query->whereRaw( 'EXTRACT(dow FROM listings_view.first_onsale_datetime) = 2' );
                    break;
                case "wednesday":
                    $query->whereRaw( 'EXTRACT(dow FROM listings_view.first_onsale_datetime) = 3' );
                    break;
                case "thursday":
                    $query->whereRaw( 'EXTRACT(dow FROM listings_view.first_onsale_datetime) = 4' );
                    break;
                case "weekend":
                    $query->whereRaw( 'EXTRACT(dow FROM listings_view.first_onsale_datetime) >= 5' );
                    break;
                case "new":
                    /* Select the most recent entry and use the date to look for other entries */
                    $newest = DB::table('listings_view')->orderBy('event_created_at','DESC')->first();
                    if ($newest) {
                        $query->whereDate( "listings_view.event_created_at", $newest->event_created_at );
                    }
                    break;
            }
        }

        // get states
        $event_state = new EventState();

        if ( $request->has( "filter" ) && !empty( $request->filter ) ) {
            switch ($request->filter) {
                case "filter-on-sale":
                    $query->where( function($query) {
                        $query->where('event_status_code', 'onsale');
                        $query->orWhere( 'event_status_code', 'onpresale');
                    } );
                    break;
                case "filter-targeted":
                    $query->where( 'event_state_id', $event_state->targeted_state_id() );
                    break;
                case "filter-excluded":
                    $query->where( 'event_state_id', $event_state->excluded_state_id() );
                    break;
                case "filter-all":
                default:
                $query->where( 'event_state_id', '!=', $event_state->excluded_state_id() );
                break;                    
            }
        } else {
            $query->where( 'event_state_id', '!=', $event_state->excluded_state_id() );
        }

        return $query->paginate( $size );
    }

    /**
     * Return listings for admin listings management.
     * Supported filters: filter-active
     * @param Request $request ['filter','search','sort','pager','page']
     * @return mixed
     */
    public function dataSearch( Request $request )
    {

        $query = \App\DataMaster::where("id",">",0);

        if ( $request->has( "dataSearch" ) && !empty( $request->dataSearch ) ) {

            $query->where( "category", "ilike", "%" . $request->dataSearch . "%" );
            //$query->orWhere( "name", "like", "%" . $request->dataSearch . "%" );  category and name hold the same data

        }

        return $query->paginate( 100 );
    }

    public function createLookup(Request $request)
    {
        if (!$request->has('event_name') || !$request->has("match_name")) {
            return response()->json( [
                'message' => "Please enter the matching name for this lookup.",
                'status'  => "error"
            ],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        $lookup = \App\EventLookup::firstOrCreate( [ 'event_name' => $request->event_name, 'match_slug' => str_slug( $request->match_name ) ],
            [
                'match_name' => $request->match_name,
                'event_slug' => str_slug( $request->event_name ),
                'match_slug' => str_slug( $request->match_name ),
                'confidence' => 100,
                'is_auto'    => false
            ] );

        return response()->json( [
            'message' => "New lookup saved successfully.",
            'new'     => false,
            'status'  => "success"
        ] );
    }

    public function associate( $event_id, \App\DataMaster $data )
    {
        // get listing view
        $event = (new Event())->where('id', '=', $event_id)->first();

        /* Create new lookup in the lookups table */
        $lookup = \App\EventLookup::firstOrCreate( [ 'event_name' => $event->name, 'match_slug' => str_slug( $data->category) ],
            [
                'match_name' => $data->category,
                'event_slug' => str_slug( $event->name ),
                'match_slug' => str_slug( $data->category ),
                'confidence' => 100,
                'is_auto' => false
            ] );

        $numListings = 0;

        // update the events table for matches
        $events = (new Event())->where('name', 'ilike', $event->name)->get();
        foreach ($events as $evt)
        {
            // save match data
            $evt->data_master_id = $data->id;
            $evt->match_confidence = 100;
            $evt->save();

            $numListings++;
        }

        return response()->json( [
            'message' => $numListings . " listing(s) updated successfully.",
            'new'     => false,
            'status'  => 'success',
            'results' => (new ListingsView())->where('event_id', '=', $event_id)->first(),
        ] );
    }

    /**
     * @param $query
     * @param string $field
     * @param string $direction
     * @return Builder
     */
    private function sort( $query, $field = "id", $direction = "asc" )
    {

        /* Reverse sort order so default sorting is in descending order */
        if ( $direction == 'asc' ) {
            $direction = " DESC NULLS LAST";
        } else {
            $direction = ' asc';
        }

        switch ($field) {

            case "status":
            case "event_name":
            case "event_day":
            case "event_date":
            case "sale_status":
            case "attraction_name":
            case "venue_name":
            case "venue_city":
            case "venue_state":
            case "venue_country":
            case "avg_ticket_price":
            case "min_price":
            case "second_highest_price":
            case "max_price":
            case "total_value":
            case "event_created_at":
            case "updated_at":
            case "first_onsale_datetime":
            case "weighted_sold":
            case "tn_tix_sold":
            case "total_sold":
            case "avg_sale_price":
            case "avg_sale_price_past":
            case "total_listed":
            case "upcoming_events":
            case "tot_per_event":
            case "sfc_roi":
            case "sfc_cogs":
            case "total_sales":
            case "sale_date":
            case 'roi_sh':
            case 'roi_low':
            case 'roi_net':
            case 'roi_second_highest':
            case 'sold_per_event':
            case 'avg_sold_price_in_date_range':
            case 'tix_sold_in_date_range':
            case 'tn_events':
                $query->orderByRaw( $field . $direction );
                break;
            default:
                $query->orderByRaw( "event_created_at DESC NULLS LAST" );
                break;
        }

        // always order by weighted sold
        $query->orderByRaw("weighted_sold DESC NULLS LAST");

        // then order by event name
        $query->orderByRaw('event_name');

        return $query;
    }


    /**
     * Update an existing listing status
     * @param Request $request
     * @param Listing $listing
     * @return mixed
     */
    public function updateStatus($event_id, $state = 'active')
    {
        $canEdit = false;
        if ( \Auth::user()->isAdmin() ) {
            $canEdit = true;
        }

        if ( !$canEdit ) {
            return response()->json( [
                'message' => "You do not have permission to edit this listing.",
                'status'  => "error"
            ],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        // get event
        $event = Event::where('id', '=', $event_id)->first();

        /* Reset to default if status changed/clicked again */
        if ($event->state->slug === $state) {
            $state = 'active';
        }

        // get state id
        $event_state = new EventState();
        $event_state_id = $event_state->getStateId($state);

        // update event status
        if ( $event->update( ['event_state_id' => $event_state_id] ) ) {
            $listing = DB::table('listings_view')
                ->where('event_id', '=', $event_id)
                ->first();

            return response()->json( [
                'message' => "Listing updated successfully.",
                'new'     => false,
                'status'  => "success",
                'results' => $listing
            ] );
        }

        return response()->json( [
            'message' => "Error saving listing. Please Try Again.",
            'status'  => "error"
        ],
            Response::HTTP_UNPROCESSABLE_ENTITY
        );

    }

    public function sendZapierWebHook($event_id)
    {
        // set Zapier end point
        $zapier_endpoint = 'https://hooks.zapier.com/hooks/catch/2587272/gjw4qj/';

        // get listings view data
        $listing = (new ListingsView())
            ->where('event_id', '=', $event_id)
            ->first()
            ->toArray();

        /* if local, don't send, but just log the data */
        if( env('APP_ENV') === 'local') {
            Log::info(print_r($listing, true));
        }

        // send request
        $response = $this->sendHttpPostRequest($zapier_endpoint, $listing);

        /* -- debug --*/
        // Log::info($response);

        // return status of success
        if( $response !== null ) {
            return response()->json( [
                'message' => "Zapier web hook sent successfully.",
                'status'  => "success"
            ] );
        }

        // return error status
        else {
            return response()->json( [
                'message' => "Error sending Zapier web hook.",
                'status'  => "error"
            ],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function sendHttpPostRequest($endpoint, $payload = [])
    {
        //init curl object
        $ch = curl_init();

        //set header options
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'post');
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $request_headers = [
            "Content-Type: application/json",
            "Accept: application/json",
        ];

        // set postfield option
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

        //set payload length
        $request_headers[] = 'Content-Length: ' . strlen(json_encode($payload));

        //set header
        curl_setopt($ch, CURLOPT_HTTPHEADER, $request_headers);

        //set url
        curl_setopt($ch, CURLOPT_URL, $endpoint);

        //run request
        $response = curl_exec($ch);

        //Log::info(json_encode($payload));

        //check for curl error
        if( curl_error($ch) ) {
            Log::error('*** Curl Error ***');
            Log::error('Request Route: ' . $endpoint);
            Log::error('Curl Error: ' . curl_error($ch));

            $response = null;
        }

        return $response;
    }

}

