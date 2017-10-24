<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class UpdateStats extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tickets:stats';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update stats for listings.';

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
        //$listings = \App\Listing::where("id",4744)->get();
        $listings = \App\Listing::all();
        foreach ($listings as $listing) {

            /* Calculate ROI for listing */
            try {
                $listing->calcRoi();
                $listing->updateSoldPerEvent();
            } catch (\Exception $e) {
                $this->error($e->getMessage());
                $this->error($e->getTraceAsString());
            }

            if ( $listing->stats->first() && ($listing->stats->roi_sh > 0 || $listing->stats->roi_low > 0)) {
                $this->info( "ROI for " . $listing->event_name . " is " . $listing->stats->roi_sh . "%" );
                $this->info( "Low ROI for " . $listing->event_name . " is " . $listing->stats->roi_low . "%" );
                $this->info( "Sold Per Event for " . $listing->event_name . " is " . $listing->stats->sold_per_event );
            }

        }
    }


}
