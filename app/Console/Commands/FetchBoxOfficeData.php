<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class FetchBoxOfficeData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tickets:boxoffice';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch box office event listings.';

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
        try {
            $scraper = new \App\Services\TicketService();
            $scraper->fetchBoxOfficeListings( 500, 10 );
        } catch (\Exception $e) {
            $this->error($e->getMessage());
            echo $e->getTraceAsString();
        }


        $log = $scraper->getLog();
        foreach ($log as $entry) {
            $this->info($entry);
        }
    }
}
