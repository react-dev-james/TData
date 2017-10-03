<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class RemoveOldListings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tickets:clean';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Soft delete all listings from previous week, update sale dates & move new onsales into current week.';

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
        $startDate = \Carbon\Carbon::now();
        $startDate->startOfWeek();

        /* Remove all listings from the previous weeek */
        $listings = \App\Listing::whereDate('created_at','<',$startDate);
        $this->info("Found " .$listings->count() . " to soft delete.");
        \App\Listing::whereDate( 'created_at', '<', $startDate )->delete();

        /* Update first on sale date for all listings */
        $listings = \App\Listing::withTrashed()->with('sales')->get();
        $numUpdated = 0;
        $this->info("Updating on sale dates for " . $listings->count() . " listings.");
        foreach ($listings as $listing) {
            if ($listing->sales->count() > 0) {
                $listing->first_onsale_date = $listing->sales->first()->sale_date;
                $listing->save();
                $numUpdated++;
            }
        }
        $this->info("Updated on sale dates for " . $numUpdated . " listings.");

        /* Move any listings with a current onsale/presale into current listings */
        $listings = \App\Listing::onlyTrashed()->with( 'sales' )->get();
        $startDate = \Carbon\Carbon::now();
        $startDate->startOfWeek();
        $endDate = $startDate->copy()->addDays( 7 );
        $numCurrent = 0;
        foreach ($listings as $listing) {
                $sale = $listing->sales->first();
                if (!$sale) {
                    continue;
                }

                if ($sale->sale_date->gte($startDate) && $sale->sale_date->lte($endDate)) {
                    $this->info( "Listing " . $listing->id . " has a current onsale, moving it to current list." );
                    $this->info( "Sale date is " . $sale->sale_date->toDateTimeString());
                    $listing->restore();
                    $numCurrent++;
                }
        }

        $this->info( "Moved  " . $numCurrent. " listings into current week." );

    }
}
