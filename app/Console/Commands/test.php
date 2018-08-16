<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminated\Console\Loggable;
use App\Models\TestLogging;

class test extends Command
{
    use Loggable;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tickets:test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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
        $this->info('test handle fired');
        $this->logAlert('alert from test command');

        $test_logging = new TestLogging();
        $test_logging->test($this);

        $this->logAlert('--after all code run---');

    }
}
