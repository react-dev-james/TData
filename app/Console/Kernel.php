<?php

namespace App\Console;

use App\Console\Commands\ImportTicketData;
use App\Console\Commands\MatchEventData;
use App\Console\Commands\FetchBoxOfficeData;
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
        ImportTicketData::class,
        MatchEventData::class,
        FetchBoxOfficeData::class
    ];

    /**
     * Define the application's command schedule.
     * beanstalkd -l 198.211.112.236 -p 11300 &
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        //$schedule->command('transqueue:stats',['--listings', '--priority','--locations'])->everyTenMinutes()->withoutOverlapping();

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
