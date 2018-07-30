<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ImportTicketData;

class ImportTicketDataMaster extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tickets:import_master_data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import master ticket data.';

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
        ImportTicketData::import();
    }
}
