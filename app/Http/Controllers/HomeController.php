<?php

namespace App\Http\Controllers;

use App\Services\ChartService;
use Illuminate\Http\Request;
use App\Rate;
use App\Listing;
use App\Location;
use App\Booking;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return redirect("/login");
    }

    public function stats(  )
    {
        $queues = [ 'stats', 'locationstats', 'properties', 'blocks', 'listings', 'subsets', 'rates' ];

        switch (env('QUEUE_DRIVER','redis')) {
            case 'redis':
                    $qm = app( \Illuminate\Queue\QueueManager::class )->connection( 'redis' );
                    $redis = $qm->getRedis();
                    $stats = [];
                    foreach ($queues as $queue) {
                        $stats[] = [
                            'name'    => $queue,
                            'pending' => $redis->llen( 'queues:' . $queue )
                        ];
                    }
                break;
            case 'beanstalkd':
                $qm = app( \Illuminate\Queue\QueueManager::class )->connection( 'beanstalkd' );
                $connection = $qm->getPheanstalk();
                $stats = [];
                foreach ($queues as $queue) {
                    $count = 0;
                    try {
                        $count = $connection->statsTube( $queue )->{'current-jobs-ready'};
                    } catch (\Exception $e) {
                        $count = 0;
                    }

                    $stats[] = [
                        'name'    => $queue,
                        'pending' => $count
                    ];
                }
                break;
        }

        return view('queueStats', ['queues' => $stats]);
    }

    public function test()
    {

        $rates = Rate::where("current",true)->whereIn("listing_id",[1,2,3])->get();
        $listings = Listing::whereIn( "id", [ 1, 2, 3 ] )->get();
        $listing = Listing::where("id",2)->first();

        $chartDates = [];
        $chartGraphs = [];
        foreach ($rates as $rate) {
            foreach ($rate->toArray() as $key => $val) {
                $chartDates[$rate->date][$key . "_" . $rate->listing_id] = $val;
            }
            
            $chartGraphs[$rate->listing_id] = [
                "id"          => "g" . $rate->listing_id,
                "title"       => "Listing " . $rate->listing_id,
                "valueField"  => "rate_" . $rate->listing_id,
                "lineAlpha" => 1,
                "lineThickness" => 2,
                "balloonText" => '$[[value]] Per Night'
            ];
        }

        $chartDates = collect($chartDates);
        $chartDates = $chartDates->values();
        $chartGraphs = collect( $chartGraphs);
        $chartGraphs = $chartGraphs->values();

        return view( 'test',  [
            'rates' => $rates,
            'listing' => $listing,
            'listings' => $listings,
            'chartRates' => $chartDates,
            'chartGraphs' => $chartGraphs,
        ] );
    }

    public function testBeds()
    {

        $listings = Listing::with("rates")->whereIn( "id", [ 1, 2, 3, 4, 5, 6 ] )->get();
        $listing = Listing::with( "rates" )->where( "id", 2 )->first();

        $chartData = [];
        $chartGraphs = [];

        $chartGraphs["occupancy"] = [
            "id"          => "g_occupancy",
            'fillAlphas'  => 0.9,
            'lineAlpha'   => 0.2,
            "title"       => "Listing Occupancy Rate",
            "valueField"  => "occupancy",
            "type"        => "column",
            "balloonText" => '[[value]]% Occupancy Rate'
        ];

        $chartGraphs["beds"] = [
            "id"          => "g_beds",
            'fillAlphas'  => 0.9,
            'lineAlpha'   => 0.2,
            "title"       => "Listing Bed Price",
            "valueField"  => "bed_price",
            "type"        => "column",
            "clustered"   => false,
            "columnWidth" => 0.6,
            "balloonText" => '$[[value]] Price Per Bed (Avg)'
        ];

        foreach ($listings as $listing) {

            $stats = $listing->stats();

            $chartData[] = [
                'label' => "Listing " . $listing->id,
                "occupancy" => $stats['percent_booked'],
                "bed_price" => $stats['price_per_bed']
            ];
        }

        $chartGraphs = collect( $chartGraphs );
        $chartGraphs = $chartGraphs->values();


        return view( 'testBeds', [
            'listing'     => $listing,
            'listings'    => $listings,
            'chartData'   => $chartData,
            'chartGraphs' => $chartGraphs,
        ] );
    }

    public function testBedsLocation( ChartService $chartService)
    {


        $locations = Location::with( "listings", "listings.rates", "listings.stats", "stats" )->whereIn( "id", [ 1, 2, 3, 4 ] )->get();

        $chartService->addLocations( $locations );
        $chartService->addChart( new \App\Charts\RevenueOccupancyChart() );

        $secondChart = new ChartService();
        $secondChart->addLocations( $locations );
        $secondChart->addChart( new \App\Charts\GoogleHeatMapChart() );


        return view( 'testBeds', [
            'locations'   => $locations,
            'chartJson'   => $chartService->getChartJson(),
            'heatMapData' => "[" . implode( ",", $secondChart->getChartDataArray() ) . "]"
        ] );
    }
}
