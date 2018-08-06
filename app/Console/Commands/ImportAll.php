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
            $this->error($e->getMessage());
            echo $e->getTraceAsString();
        }

        /* match events */
        $match_event_data = new \App\Models\MatchEventData();
        $match_event_data->match();

        /* remove old listings */
        \App\Models\RemoveOldListings::remove();

        $log = $scraper->getLog();
        foreach ($log as $entry) {
            $this->info($entry);
        }

        Log::info('-------- Import-All ended --------');
    }
}
