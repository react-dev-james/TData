<?php

namespace App\Http\Controllers\Api;

use App\Reference;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\JobLog;
use Illuminate\Http\Response;

class JobsController extends Controller
{

    public function __construct()
    {
        $this->middleware( "auth" )->except("fetch");
    }

    /**
     * Return jobLogs for admin jobLogs management.
     * @param Request $request ['filter','search','sort','pager','page']
     * @return mixed
     */
    public function index( Request $request )
    {
        $query = JobLog::where("id",">",0);

        /* Handle custom sorting on relations */
        if ( $request->has( "sort" ) ) {

            /* Reverse sort order */
            if ( $request->sort['type'] == 'asc') {
                $direction = "desc";
            } else {
                $direction = 'asc';
            }
            switch ($request->sort['name']) {

                case "type":
                case "job_type":
                case "created_at":
                    $query->orderBy( $request->sort['name'], $direction );
                    break;
                default:
                    $query->orderBy( "created_at", "desc" );
                    break;
            }

        } else {
            $query->orderBy( "created_at", "desc" );
        }

        if ( $request->has( "pager" ) ) {
            $page = $request->pager['page'];
            $size = $request->pager['size'];
        } else {
            $page = 1;
            $size = 25;
        }

        if ( $request->has( "search" ) && !empty( $request->search ) ) {
            $query->where( "message", "like", "%" . $request->search . "%" );
        }

        if ( $request->has( "filter" ) && !empty( $request->filter ) ) {
            switch ($request->filter) {
                case "filter-stats":
                    $query->where( 'job_type', 'stats' );
                    break;
                case "filter-subsets":
                    $query->where( 'job_type', 'subsets' );
                    break;
                case "filter-blocks":
                    $query->where( 'job_type', 'blocks' );
                    break;
                case "filter-rates":
                    $query->where( 'job_type', 'rates' );
                    break;
                case "filter-properties":
                    $query->where( 'job_type', 'properties' );
                    break;
                case "filter-listings":
                    $query->where( 'job_type', 'listings' );
                    break;

            }
        }

        return $query->paginate( $size );
    }

    /**
     * Add a new "starting" job to the queue. Starting jobs add new locations which will
     * then trigger subsequent jobs to be queued.
     * @param Request $request
     * @return mixed
     */
    public function queueJob(Request $request  )
    {
        $this->validate( $request, [
            'city'     => 'required|min:2',
            'state'     => 'required|min:2|max:255',
            'country'     => 'required|min:2|max:255'
        ] );

        if ( !\Auth::user()->isAdmin() ) {
            return response()->json( [
                'message' => "You do not have permission to add a new location.",
                'status'  => "error"
            ],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        /* Add multiple cities */
        if (stristr($request->city,",") !== false) {
            $cities = explode(",", $request->city);
            foreach ($cities as $city) {
                $city = trim($city);
                $job = ( new \App\Jobs\AddLocation( $city, $request->state, $request->country ) )->onQueue( 'listings' );
                dispatch( $job );
            }

            return response()->json( [
                'message' => count($cities) . " locations were added to the queue.",
                'status'  => "success"
            ] );
        }

        $job = ( new \App\Jobs\AddLocation( $request->city, $request->state, $request->country ) )->onQueue( 'listings' );
        dispatch( $job );
        return response()->json( [
            'message' => $request->city . " has been added to the queue.",
            'status'  => "success"
        ] );


    }

    /**
     * Requeue an existing job
     * @param Request $request
     * @return mixed
     */
    public function requeueJob(Request $request) {
        if (!$request->has('job_type')) {
            return response()->json( [
                'message' => "Invalid job type provided.",
                'status'  => "error"
            ],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        if ( !$request->has( 'listing_id' ) && !$request->has( 'property_id' ) && !$request->has( 'location_id' )) {
            return response()->json( [
                'message' => "Invalid item ID provided.",
                'status'  => "error"
            ],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        switch ($request->job_type) {

            case "stats":
                if ($request->has('listing_id')) {
                    $listing = \App\Listing::find($request->listing_id);
                    $listing->recordUpdate( 'stats' );
                    $job = ( new \App\Jobs\UpdateListingStats($listing))->onQueue( 'stats' );
                    dispatch( $job );
                }

                if ( $request->has( 'location_id' ) ) {
                    $location = \App\Location::find( $request->location_id );
                    $location->recordUpdate( 'stats' );
                    $job = ( new \App\Jobs\UpdateLocationStats( $location ) )->onQueue( 'locationstats' );
                    dispatch( $job );
                }

                break;

            case "rates":
                if ( $request->has( 'listing_id' ) ) {
                    $listing = \App\Listing::find( $request->listing_id );
                    $listing->recordUpdate( 'rates' );
                    $job = ( new \App\Jobs\UpdateListingRates( $listing ) )->onQueue( 'rates' );
                    dispatch( $job );
                }
                break;

            case "properties":
                if ( $request->has( 'location_id' ) ) {
                    $location = \App\Location::find( $request->location_id );
                    $location->recordUpdate( 'properties' );
                    $job = ( new \App\Jobs\UpdateLocationProperties( $location ) )->onQueue( 'properties' );
                    dispatch( $job );
                }
                break;

            case "subsets":
                if ( $request->has( 'location_id' ) ) {
                    $location = \App\Location::find( $request->location_id );
                    $location->recordUpdate( 'subsets' );
                    $job = ( new \App\Jobs\IdentifyLocationSubsets( $location ) )->onQueue( 'subsets' );
                    dispatch( $job );
                }
                break;

            case "blocks":
                if ( $request->has( 'location_id' ) ) {
                    $location = \App\Location::find( $request->location_id );
                    $location->recordUpdate( 'blocks' );
                    $job = ( new \App\Jobs\IdentifyBlockedBookings( $location ) )->onQueue( 'blocks' );
                    dispatch( $job );
                }
                break;

            case "listings":
                if ( $request->has( 'location_id' ) ) {
                    $location = \App\Location::find( $request->location_id );
                    $location->recordUpdate( 'listings' );
                    $job = ( new \App\Jobs\UpdateLocationListings( $location ) )->onQueue( 'listings' );
                    dispatch( $job );
                }
                break;
        }

        return response()->json( [
            'message' => "Job has been successfully re-queued.",
            'status'  => "success"
        ] );

    }

    /**
     * Add a new job to the job queue
     * @param Request $request
     * @return mixed
     */
    public function addToQueue( Request $request )
    {
        if ( !is_array($request->get('items')) || !$request->has('item_type') || !$request->has( 'job_type' ) ) {
            return response()->json( [
                'message' => "Invalid meta data provided, please try again.",
                'status'  => "error"
            ],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        $numAdded = 0;
        switch ($request->job_type) {

            case "stats":
                if ( $request->item_type == "listings" ) {
                    $listings = \App\Listing::whereIn('id', $request->items)->get();
                    foreach ($listings as $listing) {
                        $listing->recordUpdate( 'stats' );
                        $job = ( new \App\Jobs\UpdateListingStats( $listing ) )->onQueue( 'stats' );
                        dispatch( $job );
                        $numAdded++;
                    }
                }

                if ( $request->item_type == "locations" ) {
                    $locations = \App\Location::whereIn( 'id', $request->items )->get();
                    foreach ($locations as $location) {
                        $location->recordUpdate( 'stats' );
                        $job = ( new \App\Jobs\UpdateLocationStats( $location ) )->onQueue( 'locationstats' );
                        dispatch( $job );
                        $numAdded++;
                    }
                }

                break;

            case "listing_stats":
                    if ( $request->item_type == "locations" ) {
                        $locations = \App\Location::whereIn( 'id', $request->items )->get();
                        foreach ($locations as $location) {
                            $location->load( "listings" );
                            foreach ($location->listings as $listing) {
                                $job = ( new \App\Jobs\UpdateListingStats( $listing, true ) )->onQueue( 'stats' );
                                dispatch( $job );
                                $numAdded++;
                            }
                        }
                    }
                break;

            case "monthly_stats":
                if ( $request->item_type == "locations" ) {
                    $locations = \App\Location::whereIn( 'id', $request->items )->get();
                    foreach ($locations as $location) {
                        $location->recordUpdate( 'stats' );
                        $job = ( new \App\Jobs\UpdateMonthlyStats( $location ) )->onQueue( 'stats' );
                        dispatch( $job );
                        $numAdded++;
                    }
                }
                break;

            case "rates":
                if ( $request->item_type == "listings" ) {
                    $listings = \App\Listing::whereIn( 'id', $request->items )->get();
                    foreach ($listings as $listing) {
                        $listing->recordUpdate( 'rates' );
                        $job = ( new \App\Jobs\UpdateListingRates( $listing, true ) )->onQueue( 'rates' );
                        dispatch( $job );
                        $numAdded++;
                    }
                }

                if ( $request->item_type == "locations" ) {
                    $locations = \App\Location::whereIn( 'id', $request->items )->get();
                    foreach ($locations as $location) {
                        $location->load( "listings" );
                        foreach ($location->listings as $listing) {
                            $job = ( new \App\Jobs\UpdateListingRates( $listing, true ) )->onQueue( 'rates' );
                            dispatch( $job );
                            $numAdded++;
                        }
                    }
                }
                break;

            case "properties":
                if ( $request->item_type == "locations" ) {
                    $locations = \App\Location::whereIn( 'id', $request->items )->get();
                    foreach ($locations as $location) {
                        $location->recordUpdate( 'properties' );
                        $job = ( new \App\Jobs\UpdateLocationProperties( $location ) )->onQueue( 'properties' );
                        dispatch( $job );
                        $numAdded++;
                    }
                }
                break;

            case "subsets":
                if ( $request->item_type == "locations" ) {
                    $locations = \App\Location::whereIn( 'id', $request->items )->get();
                    foreach ($locations as $location) {
                        $location->recordUpdate( 'subsets' );
                        $job = ( new \App\Jobs\IdentifyLocationSubsets( $location ) )->onQueue( 'subsets' );
                        dispatch( $job );
                        $numAdded++;
                    }
                }
                break;

            case "blocks":
                if ( $request->item_type == "locations" ) {
                    $locations = \App\Location::whereIn( 'id', $request->items )->get();
                    foreach ($locations as $location) {
                        $location->recordUpdate( 'blocks' );
                        $job = ( new \App\Jobs\IdentifyBlockedBookings( $location ) )->onQueue( 'blocks' );
                        dispatch( $job );
                        $numAdded++;
                    }
                }
                break;

            case "realtor-properties":
                if ( $request->item_type == "locations" ) {
                    $locations = \App\Location::whereIn( 'id', $request->items )->get();
                    foreach ($locations as $location) {
                        $location->recordUpdate( 'properties' );
                        $job = ( new \App\Jobs\UpdateLocationProperties( $location, 'realtor' ) )->onQueue( 'properties' );
                        dispatch( $job );
                        $numAdded++;
                    }
                }
                break;

            case "listings":
                if ( $request->item_type == "locations" ) {
                    $locations = \App\Location::whereIn( 'id', $request->items )->get();
                    foreach ($locations as $location) {
                        $location->recordUpdate( 'listings' );
                        $job = ( new \App\Jobs\UpdateLocationListings( $location ) )->onQueue( 'listings' );
                        dispatch( $job );
                        $numAdded++;
                    }
                }
                break;
            case "homeaway-listings":
                if ( $request->item_type == "locations" ) {
                    $locations = \App\Location::whereIn( 'id', $request->items )->get();
                    foreach ($locations as $location) {
                        $job = ( new \App\Jobs\UpdateHomeAwayListings( $location ) )->onQueue( 'listings' );
                        dispatch( $job );
                        $numAdded++;
                    }
                }
                break;
        }

        return response()->json( [
            'message' => count($request->items) . " items have been added to the " . $request->job_type . " queue.",
            'status'  => "success",
            'num_added' => $numAdded
        ] );

    }
}

