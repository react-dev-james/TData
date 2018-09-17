<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\TicketMaster\ImportData;
use App\TicketMaster\MatchEvent;

class ImportTicketMaster extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tickets:ticket-master';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import ticket master data';

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
        // import data
        $import = new ImportData();
        $import->loadAllData();

        // match
        MatchEvent::runMatch();
    }
}
