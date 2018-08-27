<?php

namespace App\Http\Controllers\Api;

use App\Reference;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;
use App\Http\Controllers\Controller;
use App\User;
use App\Listing;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ListingsController extends Controller
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
            $query = Listing::with( "sales", "stats", "data" );
        } else {
            $query = Listing::onlyTrashed()->with( "sales", "stats", "data" );
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

        if ( $request->has( "search" ) && !empty( $request->search ) ) {
            $searchField = $request->get( 'searchField', 'all' );
            if ( $searchField == 'all' ) {
                $query->where( "event_name", "like", "%" . $request->search . "%" );
                $query->orWhere( "venue", "like", "%" . $request->search . "%" );
                $query->orWhere( "venue_city", "like", "%" . $request->search . "%" );
            } else if ( !empty( $searchField ) ) {
                    $query->where( $searchField, "like", "%" . $request->search . "%" );
            }

        }

        /* Date filters */
        if ( $request->has( "dateFilter" ) && !empty( $request->dateFilter ) ) {
            switch ($request->dateFilter) {
                case "monday":
                    $query->whereRaw( 'EXTRACT(dow FROM listings.first_onsale_date) = 1' );
                    /*
                    $query->join( 'sales as salesDate', function ( $join ) {
                        $join->on( 'salesDate.listing_id', '=', 'listings.id' )->where( 'salesDate.day', 'Monday' )->limit( 1 );
                    } )
                        ->whereNotNull( "salesDate.sale_date" )
                        ->select( "listings.*" );
                    */
                    break;
                case "tuesday":
                    $query->whereRaw( 'EXTRACT(dow FROM listings.first_onsale_date) = 2' );
                    break;
                case "wednesday":
                    $query->whereRaw( 'EXTRACT(dow FROM listings.first_onsale_date) = 3' );
                    break;
                case "thursday":
                    $query->whereRaw( 'EXTRACT(dow FROM listings.first_onsale_date) = 4' );
                    break;
                case "weekend":
                    $query->whereRaw( 'EXTRACT(dow FROM listings.first_onsale_date) >= 5' );
                    break;
                case "new":
                    /* Select the most recent entry and use the date to look for other entries */
                    $newest = \App\Listing::orderBy('created_at','DESC')->first();
                    if ($newest) {
                        $query->whereDay( "listings.created_at", $newest->created_at->day );
                    }
                    break;
            }
        }

        if ( $request->has( "filter" ) && !empty( $request->filter ) ) {
            switch ($request->filter) {
                case "filter-on-sale":
                    $query->where( function($query) {
                        $query->where('sale_status', 'OnSale');
                        $query->orWhere( 'sale_status', 'OnPresale');
                    } );
                    break;
                case "filter-targeted":
                    $query->where( 'status', 'targeted' );
                    break;
                case "filter-excluded":
                    $query->where( 'status', 'excluded' );
                    break;
                case "filter-future":
                    $query->where( 'sale_status', 'Future' );
                    break;
                case "filter-all":
                default:
                $query->where( 'status','!=','excluded' );
                break;                    
            }
        } else {
            $query->where( 'status', '!=', 'excluded' );
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

            $query->where( "category", "like", "%" . $request->dataSearch . "%" );
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

    public function associate( Request $request, Listing $listing, \App\DataMaster $data )
    {
        /* Create new lookup in the lookups table */
        $lookup = \App\EventLookup::firstOrCreate( [ 'event_name' => $listing->event_name, 'match_slug' => str_slug( $data->category) ],
            [
                'match_name' => $data->category,
                'event_slug' => str_slug( $listing->event_name ),
                'match_slug' => str_slug( $data->category ),
                'confidence' => 100,
                'is_auto' => false
            ] );

        $listing->performer = $data->category;
        $listing->data_master_id = $data->id;
        $listing->confidence = 100;
        $listing->save();

        /* Recalc ROI */
        //$listing->fresh();
        $listing->calcRoi($data);

        /* Load relations */
        $listing->load('data','sales','updates', 'stats');

        $numListings = 1;

        /* Check lookups table for other matching listings */
        $listings = \App\Listing::where("event_name", $lookup->event_name)->where("id","!=", $listing->id)->get();
        foreach ($listings as $otherListing) {
            $numListings++;
            $otherListing->performer = $lookup->match_name;
            $listing->data_master_id = $data->id;
            $listing->confidence = 100;
            $otherListing->save();

            /* Recalc ROI */
            //$otherListing->fresh();
            $otherListing->calcRoi($data);
        }

        return response()->json( [
            'message' => $numListings . " listing(s) updated successfully.",
            'new'     => false,
            'status'  => "success",
            'results' => $listing
        ] );
    }

    /**
     * @param Builder $query
     * @param string $field
     * @param string $direction
     * @return Builder
     */
    private function sort( Builder $query, $field = "id", $direction = "asc" )
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
            case "category":
            case "event_day":
            case "event_date":
            case "sale_status":
            case "performer":
            case "performer_normalized":
            //case "event_name":
            case "venue":
            case "venue_capacity":
            case "venue_city":
            case "venue_state":
            case "venue_country":
            case "avg_ticket_price":
            case "low_ticket_price":
            case "high_ticket_price":
            case "total_value":
            case "created_at":
            case "updated_at":
            case "first_onsale_date":
            case "weighted_sold":
                $query->orderBy( $field, $direction );
                break;
            case "tn_tix_sold":
            case "total_sold":
            case "avg_sale_price":
            case "avg_sale_price_past":
            case "total_listed":
            case "upcoming_events":
            case "tot_per_event":
            case "sfc_roi":
            case "sfc_cogs":
                $query->leftJoin( 'data_master', function ( $join ) {
                        $join->on( 'data_master.id', '=', 'listing.data_master_id' );
                    } )
                    ->orderByRaw( '"data_master"."' . $field . '"' . $direction )
                    ->select( "listings.*" );
                break;
            case "total_sales":
                $query->leftJoin( 'data_master', function ( $join ) {
                        $join->on( 'data_master.id', '=', 'listing.data_master_id' );
                    } )
                    ->orderByRaw( '"data_master"."total_sold" - "data_master"."tn_tix_sold"' . $direction )
                    ->select( "listings.*" );
                break;
            case "sale_date":
                $query->leftJoin( 'sales', function ( $join ) {
                    $join->on( 'sales.listing_id', '=', 'listings.id' )->orderBy('sale_date','ASC')->limit(1);
                } )
                    ->orderByRaw( '"sales"."' . $field . '"' . $direction )
                    ->select( "listings.*" );
                break;
            case 'roi_sh':
            case 'roi_low':
            case 'roi_net':
            case 'sold_per_event':
            case 'avg_sold_price_in_date_range':
            case 'tix_sold_in_date_range':
            case 'tn_events':
                $query->leftJoin( 'stats as statData', function ( $join ) {
                    $join->on( 'statData.listing_id', '=', 'listings.id' );
                } )
                    ->orderByRaw( '"statData"."' . $field . '"' . $direction )
                    ->select( "listings.*" );
                break;
            default:
                $query->orderByRaw( "created_at DESC NULLS LAST" );
                break;
        }

        $query->orderByRaw("weighted_sold DESC NULLS LAST");

        // add event name as sort
        $query->orderByRaw('event_name');

        return $query;
    }

    /**
     * Load an existing listing
     *
     * @return \Illuminate\Http\Response
     */
    public function fetch( Request $request, Listing $listing )
    {

        $listing->load("stats","sales","updates");
        return response()->json( [
            'message' => "Listing loaded for editing.",
            'status'  => "success",
            'results' => $listing
        ] );
    }

    /**
     * Update an existing listing status
     * @param Request $request
     * @param Listing $listing
     * @return mixed
     */
    public function updateStatus( Request $request, Listing $listing, $status = 'active' )
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

        /* Reset to default if status changed/clicked again */
        if ($listing->status == $status) {
            $status = 'active';
        }

        if ( $listing->update( ['status' => $status] ) ) {
            $listing->load( "sales", "data", "updates", "stats" );
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

    /**
     * Update an existing listing
     * @param Request $request
     * @param Listing $listing
     * @return mixed
     */
    public function update( Request $request, Listing $listing )
    {
        $canEdit = false;
        if (\Auth::user()->isAdmin() )
        {
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

        /* Default validators */
        $validators = [
            'name'       => 'required|min:5|max:255',
        ];

        $this->validate( $request, $validators );

        $update = $request->except(
            "id"
        );


        if ( $listing->update( $update ) ) {
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

    /**
     * Delete a listing.
     * @param Request $request
     * @param User $user
     * @return mixed
     */
    public function delete( Request $request, Listing $listing )
    {

        /* Confirm user has permission to delete the listing */
        $canDelete= false;
        if (\Auth::user()->isAdmin())
         {
            $canDelete = true;
        }

        if ( !$canDelete ) {
            return response()->json( [
                'message' => "This listing can not be deleted. You may not have permission to delete it.",
                'status'  => "error"
            ],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        if ( $listing->delete() ) {
            return response()->json( [
                'message' => "Listing deleted successfully.",
                'status'  => "success"
            ] );
        }

        return response()->json( [
            'message' => "Error deleting listing. Please Try Again.",
            'status'  => "error"
        ],
            Response::HTTP_UNPROCESSABLE_ENTITY
        );

    }

    public function sendZapierWebHook($listing_id)
    {
        // set Zapier end point
        $zapier_endpoint = 'https://hooks.zapier.com/hooks/catch/2587272/gjw4qj/';

        // get listing with data
        $listing = Listing::where('id', '=', $listing_id)->with('stats')->firstOrFail()->toArray();

        /* change it to local so we don't send it to the production webhook for testing */
        if( env('APP_ENV') === 'local') {
            $zapier_endpoint = 'https://hooks.zapier.com/hooks/catch/3592924/g5u56f/';
        }

        // get listing with data
        $listing = Listing::where('id', '=', $listing_id)->with('stats', 'data')->firstOrFail()->toArray();

        //Log::info('----- data sent to Zapier web hook ----');
        //Log::info(print_r($listing, true));

        // send request
        $response = $this->sendHttpPostRequest($zapier_endpoint, $listing);

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

