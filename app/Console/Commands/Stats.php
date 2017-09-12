<?php

namespace App\Console\Commands;

use App\Listing;
use App\Location;
use App\Property;
use App\Services\ZillowService;
use Illuminate\Console\Command;

/**
 * Class Stats
 * @package App\Console\Commands
 * Examples:
 *
 * Update all stats: php artisan stats --listings --scores --locations
 */
class Stats extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stats 
    {--listings : Update stats for listings.} 
    {--priority : Update listings with no stats yet.}
    {--scores : Update listing profit scores.} 
    {--locations : Update stats for locations.} 
    {--s|start=0 : Query offset.} 
    {--l|limit=100000 : Query limit}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update stats for listings & locations.';

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

        $updateListings = $this->option( 'listings' );
        $updateLocations = $this->option( 'locations' );
        $updatePriority = $this->option( 'priority' );
        $updateScore = $this->option( 'scores' );
        $start = $this->option('start');
        $limit = $this->option('limit');

        $this->info("Starting with offset of {$start} and limit of {$limit}.");

        if ( $updatePriority) {

            $this->info( "Updating priority listing stats." );
            $listings = Listing::doesntHave( "stats" )->with( 'stats', 'locations', 'updates' )->where( "id", ">", 0 )->orderBy( 'updated_at', 'ASC' )->offset( $start )->limit( $limit )->get();
            $this->info( "Found " . $listings->count() . " listings to update." );

            foreach ($listings as $listing) {
                try {
                    if ( $updateListings ) {
                        $listing->updateStats();
                        $listing->recordUpdate( 'stats' );
                    }

                    if ( $updateScore ) {
                        $listing->updateProfitScore();
                    }
                } catch ( \Exception $e ) {
                    $this->info( $e->getMessage() );
                    continue;
                }
            }
        }

        if ($updateListings || $updateScore) {

            if ($updateListings) $this->info("Updating listings stats.");
            if ($updateScore) $this->info("Updating listings profit scores.");
            $listings = Listing::with('stats', 'locations', 'updates')->where("id",">",0)->orderBy('updated_at', 'ASC')->offset($start)->limit($limit)->get();
            $this->info("Found " . $listings->count() . " listings to update.");

            foreach ($listings as $listing) {
                try {
                    if ( $updateListings ) {
                        $listing->updateStats();
                        $listing->recordUpdate( 'stats' );
                    }

                    if ( $updateScore ) {
                        $listing->updateProfitScore();
                    }
                } catch (\Exception $e) {
                    $this->info($e->getMessage());
                    continue;
                }
            }
        }

        if ($updateLocations) {
            $scraper = new \App\Services\AirBnbService();
            $locations = Location::with('stats','listings')->where('id','>',0)->orderBy( 'updated_at', 'ASC' )->offset( $start )->limit( $limit )->get();
            $this->info( "Found " . $locations->count() . " locations to update." );
            foreach ($locations as $location) {

                try {
                    $scraper->updateLocationStats( $location );
                    $location->updateStats();
                    $location->recordUpdate( 'stats' );
                } catch ( \Exception $e ) {
                    $this->info( $e->getMessage() );
                    continue;
                }

            }
        }


    }


}
