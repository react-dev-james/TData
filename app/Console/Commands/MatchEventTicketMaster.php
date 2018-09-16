<?php

namespace App\Console\Commands;

use App\TicketMaster\MatchEvent;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class MatchEventTicketMaster extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tickets:ticket-master-match';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Match ticket master events with ticket data.';


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
        MatchEvent::runMatch();

        /*try {
            $match_event_data = new MatchEvent();
            $match_event_data->runMatch();
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            Log::error($e->getTraceAsString());

            echo $e->getMessage();
        }*/
    }

}
