<?php

namespace App\Console;

use App\Console\Commands\ImportTicketData;
use App\Console\Commands\MatchEventData;
use App\Console\Commands\FetchBoxOfficeData;
use App\Console\Commands\UpdateStats;
use App\Console\Commands\RemoveOldListings;
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
        FetchBoxOfficeData::class,
        UpdateStats::class,
        RemoveOldListings::class
    ];

    /**
     * Define the application's command schedule.
     * beanstalkd -l 198.211.112.236 -p 11300 &
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('tickets:boxoffice')->dailyAt('07:00');
        $schedule->command('tickets:match')->dailyAt('07:20');
        $schedule->command('tickets:clean')->dailyAt('07:28');
        $schedule->command('tickets:stats')->dailyAt('07:35');
        $schedule->command('tickets:stats')->dailyAt('08:00');

        $schedule->command( 'tickets:boxoffice' )->dailyAt( '11:00' );
        $schedule->command( 'tickets:match' )->dailyAt( '11:20' );
        $schedule->command( 'tickets:clean' )->dailyAt( '11:28' );
        $schedule->command( 'tickets:stats' )->dailyAt( '11:35' );
        $schedule->command( 'tickets:stats' )->dailyAt( '12:00' );

        $schedule->command( 'tickets:boxoffice' )->dailyAt( '16:00' );
        $schedule->command( 'tickets:match' )->dailyAt( '16:20' );
        $schedule->command( 'tickets:clean' )->dailyAt( '16:28' );
        $schedule->command( 'tickets:stats' )->dailyAt( '16:35' );
        $schedule->command( 'tickets:stats' )->dailyAt( '17:00' );
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
