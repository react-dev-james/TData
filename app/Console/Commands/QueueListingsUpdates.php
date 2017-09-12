<?php

namespace App\Console\Commands;

use App\Listing;
use App\Location;
use Illuminate\Console\Command;

class QueueListingsUpdates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'transqueue:listings
    {--l|limit=500 : Query limit}
    {--priority : Update listings with no stats yet.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Queue stats updates for listings & locations.';

    const MAX_LISTINGS_TIMES = 3;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {

        $updatePriority = $this->option( 'priority' );
        $limit = $this->option( 'limit' );

        /*
         * @todo - Add site settings to control queue times
         * Only fetch listings that were last checked more than 2 weeks ago.
         */

        $dateOffset = date("Y-m-d H:i:s", time() - 60 * 60 * 24 * 730);
        $this->info("Updating items which were last updated before: ". $dateOffset);


        if (!$updatePriority) {

            $this->info( "Selecting default listings." );
            $locations = Location::whereHas( 'updates', function ( $query ) use ( $dateOffset ) {
                $query->where( "listings_at", "<", $dateOffset );
                $query->orWhere( "listings_at", null );
            } )->limit( $limit )->with( 'updates' )->get();

            if ( $locations->count() == 0 ) {
                $this->info( "All locations have listings relations." );
            }

            foreach ($locations as $location) {

                $location->recordUpdate( 'listings' );
                $job = ( new \App\Jobs\UpdateLocationListings( $location ) )->onQueue( 'listings' );
                dispatch( $job );
            }

            $this->info( $locations->count() . " Locations Queued For Listings Update" );
        }

        if ( $updatePriority ) {

            $this->info( "Selecting priority listings." );
            /* Locations with no properties */
            $locations = Location::whereDoesntHave( 'listings' )->limit( $limit )->with( 'updates' )->get();

            if ( $locations->count() == 0 ) {
                $this->info( "All locations have listings relations." );
            }

            foreach ($locations as $location) {

                if ($location->updates && $location->updates->listings_times > self::MAX_LISTINGS_TIMES) {
                    continue;
                }

                $location->recordUpdate( 'listings' );
                $job = ( new \App\Jobs\UpdateLocationListings( $location ) )->onQueue( 'listings' );
                dispatch( $job );
            }

            $this->info( $locations->count() . " Locations Queued For Listings Update" );

        }

    }
}
