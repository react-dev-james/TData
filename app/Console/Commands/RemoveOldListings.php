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
    protected $description = 'Soft delete all listings from previous week.';

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

        $listings = \App\Listing::whereDate('created_at','<',$startDate);
        $this->info("Found " .$listings->count() . " to soft delete.");
        \App\Listing::whereDate( 'created_at', '<', $startDate )->delete();
    }
}
