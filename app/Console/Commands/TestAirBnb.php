<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Location;
use App\Listing;

class TestAirBnb extends PhantomCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:airbnb';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test AirBnb connection.';

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

        /*
        $json = file_get_contents( base_path( "storage/app/webdriver/" ) . 'AparmentListingDetailsFixed2.json');
        $details = json_decode($json, true);
        print_r($details);
        exit();
        */

        $scraper = new \App\Services\ApartmentsService();
        $scraper->useCrawlera();

        $location = \App\Location::where("city","Green River")->where("state","Utah")->get()->first();
        foreach ($location->listings as $listing) {
            $listing->updateLeaseProfitScore();
            $listing->updateProfitScore();
        }

        $location->updateStats();

        exit();

        $scraper->setLocation($location);
        $results = $scraper->getListings(1, 1, 5);
        print_r($scraper->getLog());


    }

    public function insertRateUpdates() {
        $listings = \App\Listing::whereHas("rates")->with("updates")->get();
        $this->info("Loaded " . $listings->count() . " listings with rates.");

        $numWithout = 0;
        foreach ($listings as $listing) {
            if ($listing->updates && $listing->updates->rates_at == null) {
                $numWithout++;
                echo ".";
                $listing->recordUpdate("rates");
            }
        }

        $this->info( "Updated " . $numWithout . " listings to set rates_at date." );
    }

    public function testInstantBooking(  )
    {
        $scraper = new \App\Services\AirBnbService();
        $listings = Listing::with( "stats" )->where( 'potential_outlier', true )->orderBy( "created_at", 'desc' )->skip( 50 )->limit( 50 )->get();

        $total = 0;
        $instantBook = 0;
        $instantBookFullOccupancy = 0;
        $noInstantBookFullOccupancy = 0;
        foreach ($listings as $listing) {
            $this->display( "Checking " . $listing->name . " to see if it is instant bookable." );
            $instantBookable = $scraper->hasInstantBook( $listing );

            if ( $instantBookable ) {
                $instantBook++;
                $this->display( "-> Yes" );

                if ( $listing->stats->percent_booked == 100 ) {
                    $instantBookFullOccupancy++;
                }
            } else {
                $this->display( "-> No" );
                if ( $listing->stats->percent_booked == 100 ) {
                    $noInstantBookFullOccupancy++;
                }
            }

            $total++;

            sleep( rand( 1, 5 ) );
        }

        $this->display( $instantBook . " out of " . $total . " listings are instant bookable." );
        $this->display( $instantBookFullOccupancy . "listings with 100% occupancy are instant bookable." );
        $this->display( $noInstantBookFullOccupancy . "listings with 100% occupancy are NOT instant bookable." );
    }


}
