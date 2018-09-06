<?php
/**
 * @desc - tickets:boxoffice  - tickets:match - tickets:clean - tickets:stats
 */

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ImportAll extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tickets:import-all';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Runs all import jobs.';

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
        Log::info('-------- Import-All started --------');

        try {
            /* scraper */
            $scraper = new \App\Services\TicketService();
            $listingData = $scraper->fetchBoxOfficeListings( 500, 10 );
            //print_r($listingData);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            Log::error($e->getTraceAsString());

            $this->error($e->getMessage());
        }

        /* remove old listings */
        try{
            \App\Import\RemoveOldListings::remove();
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            Log::error($e->getTraceAsString());

            $this->error($e->getMessage());
        }

        /* match events */
        try {
            $match_event_data = new \App\Import\MatchEventData();
            $match_event_data->match();
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            Log::error($e->getTraceAsString());

            $this->error($e->getMessage());
        }

        /* update stats */
        try {
            \App\Import\UpdateStats::update();
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            Log::error($e->getTraceAsString());

            $this->error($e->getMessage());
        }

        /* this is not implemented yet
        $log = $scraper->getLog();
        foreach ($log as $entry) {
            $this->info($entry);
        }
        */

        Log::info('-------- Import-All ended --------');
    }
}
