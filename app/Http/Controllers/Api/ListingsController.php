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
        /* Load custom saved items if a report ID is provided */
        $report = false;
        if (!empty($request->get('reportId'))) {
            $report = \App\Report::find($request->reportId);
        }

        if ($report) {
            $query = Listing::whereIn('listings.id', $report->item_array)->with( "locations", "stats" );
        } else {
            $query = Listing::with( "locations", "stats" );
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
                $query->where( "name", "like", "%" . $request->search . "%" );
                $query->orWhere( "city", "like", "%" . $request->search . "%" );
            } else if ( !empty( $searchField ) ) {
                    $query->where( $searchField, "like", "%" . $request->search . "%" );
            }

        }

        if ( $request->has( "filter" ) && !empty( $request->filter ) ) {
            switch ($request->filter) {
                case "filter-outliers":
                    $query->where( function($query) {
                        $query->where('outlier', true);
                        $query->orWhere('potential_outlier', true);
                    } );
                    break;
                case "filter-no-outliers":
                    $query->where('outlier', false);
                    $query->where('potential_outlier', false);
                    break;
                case "filter-homes":
                    $query->where( "room_type", "home" );
                    break;
                case "filter-condos":
                    $query->where( "room_type", "condo" );
                    break;
            }
        }

        return $query->paginate( $size );
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

            case "source":
            case "status":
            case "name":
            case "created_at":
            case "beds":
            case "bedrooms":
            case "capacity":
            case "current_rate":
            case "room_type":
            case "profit_score":
                $query->orderBy( $field, $direction );
                break;
            case "outlier":
                $query->orderBy( $field, $direction );
                $query->orderBy( 'potential_outlier', $direction );
                break;
            case "percent_booked":
            case "price_per_bed":
            case "projected_revenue":
                $query->join( 'stats', function ( $join ) {
                    $join->on( 'stats.listing_id', '=', 'listings.id' );
                    $join->where( 'stats.primary', true );
                } )
                    ->orderBy( 'stats.' . $field, $direction )
                    ->select( "listings.*" );
                break;
            default:
                $query->orderBy( "created_at", "desc" );
                break;
        }

        return $query;
    }

    /**
     * Create a new listing
     * @param Request $request
     */
    public function create( Request $request )
    {
        if ( !\Auth::user()->isClient && !\Auth::user()->isAdmin() ) {
            return response()->json( [
                'message' => "Only clients can create new listings.",
                'status'  => "error"
            ],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        $this->validate( $request, [
            'title'         => 'required|min:5|max:180',
            'description'   => 'required|min:5|max:2048',
            'payment_terms' => 'required',
        ] );

        /* Validate all files at least have a name */
        $fileIds = [];
        $fileAttributes = [];
        if ( $request->has( "files" ) ) {
            foreach ($request->get( "files" ) as $file) {
                if ( empty( $file['name'] ) ) {
                    return response()->json( [
                        'message' => "Please make sure all uploaded files have a name set.",
                        'status'  => "error"
                    ],
                        Response::HTTP_UNPROCESSABLE_ENTITY
                    );
                }

                $fileIds[] = $file['id'];
                $fileAttributes[$file['id']] = [
                    'name'        => $file['name'],
                    'description' => $file['description']
                ];
            }
        }

        $newListing = $request->except( "token", "files" );
        $newListing['listing_id'] = str_random( 5 );
        $newListing['status'] = "new";
        $newListing['type'] = "standard";
        $newListing['end_reason'] = "";
        $newListing['payment_terms'] = "";

        $listing = \Auth::user()->clientListings()->create( $newListing );
        $listing->attachMediaExtra( $fileIds, "files", $fileAttributes );

        return response()->json( [
            'message' => "Listing saved successfully. ",
            'new'     => true,
            'status'  => "success",
            'results' => $listing
        ] );

    }

    /**
     * Load an existing listing
     *
     * @return \Illuminate\Http\Response
     */
    public function fetch( Request $request, Listing $listing )
    {

        $listing->load("stats","duplicates","locations","updates");
        return response()->json( [
            'message' => "Listing loaded for editing.",
            'status'  => "success",
            'results' => $listing
        ] );
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

        /* Validate all files at least have a name */
        $fileIds = [];
        $fileAttributes = [];
        if ( $request->has( "files" ) ) {
            foreach ($request->get( "files" ) as $file) {
                if ( empty( $file['name'] ) ) {
                    return response()->json( [
                        'message' => "Please make sure all uploaded files have a name set.",
                        'status'  => "error"
                    ],
                        Response::HTTP_UNPROCESSABLE_ENTITY
                    );
                }

                $fileIds[] = $file['id'];
                $fileAttributes[$file['id']] = [
                    'name'        => $file['name'],
                    'description' => $file['description']
                ];
            }
        }
        $update = $request->except(
            "id"
        );


        if ( $listing->update( $update ) ) {
            $listing->syncMediaExtra( $fileIds, "files", $fileAttributes );
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
    public function updateOutlierStatus( Request $request, Listing $listing )
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

        $listing->outlier = $request->outlier;
        $listing->potential_outlier = $request->potential_outlier;

        if ($listing->save()) {
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

