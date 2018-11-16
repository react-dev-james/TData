<?php

namespace App\Console;

use App\Console\Commands\ImportSocialMedia;
use App\Console\Commands\ImportTicketDataMaster;
use App\Console\Commands\ImportTicketMaster;
use App\Console\Commands\ImportTicketNetwork;
use App\Console\Commands\MatchEventData;
use App\Console\Commands\FetchBoxOfficeData;
use App\Console\Commands\MatchEventTicketMaster;
use App\Console\Commands\test;
use App\Console\Commands\UpdateStats;
use App\Console\Commands\RemoveOldListings;
use App\Console\Commands\ImportAll;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        ImportTicketDataMaster::class,
        ImportTicketNetwork::class,
        MatchEventData::class,
        FetchBoxOfficeData::class,
        UpdateStats::class,
        RemoveOldListings::class,
        ImportAll::class,
        test::class,
        ImportTicketMaster::class,
        MatchEventTicketMaster::class,
        ImportSocialMedia::class,
    ];

    /**
     * Define the application's command schedule.
     * beanstalkd -l 198.211.112.236 -p 11300 &
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $path = storage_path('logs/jobs/');

        $schedule->command('tickets:ticket-master 0')->dailyAt('06:30')->sendOutputTo($path . 'tm-api.' . date("Y-m-d") . '-7.30am.txt');
        $schedule->command('tickets:import-all')->dailyAt('07:00')->sendOutputTo($path . 'import-all.' . date("Y-m-d") . '-8am.txt');

        $schedule->command('tickets:ticket-master 1')->dailyAt('10:30')->sendOutputTo($path . 'tm-api.' . date("Y-m-d") . '-11.30pm.txt');
        $schedule->command('tickets:import-all')->dailyAt('11:00')->sendOutputTo($path . 'import-all.' . date("Y-m-d") . '-noon.txt');

        $schedule->command('tickets:ticket-master 2')->dailyAt('13:30')->sendOutputTo($path . 'tm-api.' . date("Y-m-d") . '-4.30pm.txt');
        $schedule->command('tickets:import-all')->dailyAt('16:00')->sendOutputTo($path . 'import-all.' . date("Y-m-d") . '-5pm.txt');

        $schedule->command('tickets:ticket-master 3')->dailyAt('18:30')->sendOutputTo($path . 'tm-api.' . date("Y-m-d") . '-7.30pm.txt');
        $schedule->command('tickets:import-all')->dailyAt('19:00')->sendOutputTo($path . 'import-all.' . date("Y-m-d") . '-8pm.txt');
    }

    /**
     * Register the Closure based commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        require base_path('routes/console.php');
    }
}
