<?php

namespace App\Console\Commands;

use App\SocialMedia\LastFm;
use Illuminate\Console\Command;

class test extends Command
{
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
        $last_fm = new LastFm(config('api.last_fm.api_key'));
        $response = $last_fm->getArtistData('taylor swift');

        $this->info(print_r($response));
    }
}
