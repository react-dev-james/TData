<?php

namespace App\Jobs;

use App\Listing;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Carbon\Carbon;

class UpdateMonthlyStats implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 900;

    /**
     * @var Listing
     */
    protected $location;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct( \App\Location $location)
    {
        $this->location = $location;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {

        $today = Carbon::now();
        $startDate = $today->firstOfYear();
        $endDate = $startDate->copy()->endOfMonth();
        $x = 0;
        while ($x <= 12) {
            $x++;

            /* Combined Stats */
            $this->location->setSource("combined");
            $this->location->getPeriodStats( $startDate->toDateString(), $endDate->toDateString(), true, true );

            /* AirBnb Stats */
            $this->location->setSource( "airbnb" );
            $this->location->getPeriodStats( $startDate->toDateString(), $endDate->toDateString(), true, true );

            /* HomeAway Stats */
            $this->location->setSource( "homeaway" );
            $this->location->getPeriodStats( $startDate->toDateString(), $endDate->toDateString(), true, true );

            $startDate->addMonth( 1 );
            $endDate = $startDate->copy()->endOfMonth();
        }

        \App\JobLog::create( [
            'location_id' => $this->location->id,
            'job_type'   => 'monthly_stats',
            'type'       => 'info',
            'message'    => "Updated monthly for location " . $this->location->id,
            'payload'    => @json_encode( [] )
        ] );

    }
}
