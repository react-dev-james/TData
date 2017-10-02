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
            $query = Listing::with( "sales", "data", "updates", "stats" );
        } else {
            $query = Listing::onlyTrashed()->with( "sales", "data", "updates", "stats" );
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
            $page = $request->pager['page'];
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
                    $query->join( 'sales as salesDate', function ( $join ) {
                        $join->on( 'salesDate.listing_id', '=', 'listings.id' )->where( 'salesDate.day', 'Monday' )->limit( 1 );
                    } )
                        ->whereNotNull( "salesDate.sale_date" )
                        ->select( "listings.*" );
                    break;
                case "tuesday":
                    /*
                    $query->where( function ( $query ) {
                        $query->where( 'sale_status', 'OnSale' );
                        $query->orWhere( 'sale_status', 'OnPresale' );
                    } );

                    $query->whereRaw( 'weekday(listings.created_at) = 1' );
                    */

                    $query->join( 'sales as salesDate', function ( $join ) {
                        $join->on( 'salesDate.listing_id', '=', 'listings.id' )->where( 'salesDate.day', 'Tuesday' )->limit(1);
                    } )
                        ->whereNotNull("salesDate.sale_date")
                        ->select( "listings.*" );
                    /*
                    $query->whereHas( 'sales', function ( $query ) {
                        $query->where('day','Tuesday');
                    } );
                    */

                    break;
                case "wednesday":
                    $query->join( 'sales as salesDate', function ( $join ) {
                        $join->on( 'salesDate.listing_id', '=', 'listings.id' )->where( 'salesDate.day', 'Wednesday' )->limit( 1 );
                    } )
                        ->whereNotNull( "salesDate.sale_date" )
                        ->select( "listings.*" );
                    break;
                case "thursday":
                    $query->join( 'sales as salesDate', function ( $join ) {
                        $join->on( 'salesDate.listing_id', '=', 'listings.id' )->where( 'salesDate.day', 'Thursday' )->limit( 1 );
                    } )
                        ->whereNotNull( "salesDate.sale_date" )
                        ->select( "listings.*" );
                    break;
                case "weekend":
                    $query->join( 'sales as salesDate', function ( $join ) {
                        $join->on( 'salesDate.listing_id', '=', 'listings.id' )->whereRaw( 'weekday(salesDate.sale_date) >= 4' )->limit( 1 );
                    } )
                        ->whereNotNull( "salesDate.sale_date" )
                        ->select( "listings.*" );
                    break;
                case "new":
                    /* Select the most recent entry and use the date to look for other entries */
                    $newest = \App\Listing::orderBy('created_at','DESC')->first();
                    $query->whereDay("listings.created_at", $newest->created_at->day );
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

        $query = \App\Data::where("id",">",0);

        if ( $request->has( "dataSearch" ) && !empty( $request->dataSearch ) ) {

            $query->where( "category", "like", "%" . $request->dataSearch . "%" );
            $query->orWhere( "name", "like", "%" . $request->dataSearch . "%" );

        }

        return $query->paginate( 100 );
    }

    public function associate( Request $request, Listing $listing, \App\Data $data )
    {
        /* Create new lookup in the lookups table */
        \App\EventLookup::firstOrCreate( [ 'event_name' => $listing->event_name ],
            [
                'match_name' => $data->category,
                'event_slug' => str_slug( $listing->event_name ),
                'match_slug' => str_slug( $data->category ),
                'confidence' => 100,
                'is_auto' => false
            ] );

        $listing->performer = $data->category;
        $listing->save();

        /* Create new entry in the listing_data pivot table */
        $listing->data()->sync( [ $data->id => [ 'confidence' => 100 ]] );

        /* Recalc ROI */
        $listing->fresh();
        $listing->calcRoi();

        /* Load relations */
        $listing->load('data','sales','updates', 'stats');

        return response()->json( [
            'message' => "Listing updated successfully.",
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
            $direction = "desc";
        } else {
            $direction = 'asc';
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
            case "event_name":
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
                $query->orderBy( $field, $direction );
                break;
            case "avg_sale_price":
            case "avg_sale_price_past":
            case "total_sales":
            case "total_sales_past":
            case "total_listed":
                $query->join( 'listing_data', function ( $join ) {
                    $join->on( 'listing_data.listing_id', '=', 'listings.id' );
                } )
                    ->join( 'data', function ( $join ) {
                        $join->on( 'data.id', '=', 'listing_data.data_id' );
                    } )
                    ->orderBy( 'data.' . $field, $direction )
                    ->select( "listings.*" );
                break;
            case "sale_date":
                $query->join( 'sales', function ( $join ) {
                    $join->on( 'sales.listing_id', '=', 'listings.id' )->orderBy('sale_date','ASC')->limit(1);
                } )
                    ->orderBy( 'sales.' . $field, $direction )
                    ->select( "listings.*" );
                break;
            case 'roi_sh':
            case 'roi_low':
                $query->join( 'stats as statData', function ( $join ) {
                    $join->on( 'statData.listing_id', '=', 'listings.id' );
                } )
                    ->orderBy( 'statData.' . $field, $direction )
                    ->select( "listings.*" );
                break;
            default:
                $query->orderBy( "created_at", "desc" );
                break;
        }

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

}

