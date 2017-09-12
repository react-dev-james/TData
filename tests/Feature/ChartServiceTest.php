<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Location;

class ChartServiceTest extends TestCase
{

    public function testChartServiceJsonOutput()
    {
        $chartService = \App::make(\App\Services\ChartService::class);
        $chartService->addAxis([
            "id" => "price",
            "title" => "Price Per Bed & Occupancy Rate",
            "position" => "left",
        ], "$");

        $chartService->addAxis( [
            "id"       => "revenue",
            "title"    => "Projected Revenue",
            "position" => "right"
        ], "%" );

        $chartService->addGraph( [
            "valueAxis"   => "price",
            "id"          => "g_beds",
            'fillAlphas'  => 0.9,
            'lineAlpha'   => 0.2,
            "title"       => "Average Price Per Bed",
            "valueField"  => "bed_price",
            "type"        => "column",
            "balloonText" => '$[[value]] Price Per Bed (Avg)'
        ]);

        $chartService->addGraph( [
            "valueAxis"   => "price",
            "id"          => "g_occupancy",
            'fillAlphas'  => 0.7,
            'lineAlpha'   => 0.2,
            "title"       => "Average Occupancy Rate",
            "valueField"  => "occupancy",
            "type"        => "column",
            "clustered"   => false,
            "columnWidth" => 0.6,
            "balloonText" => '[[value]]% Occupancy Rate'
        ]);

        $chartService->addGraph( [
            "valueAxis"   => "revenue",
            "id"          => "g_revenue",
            'fillAlphas'  => 0.2,
            'lineAlpha'   => 0.9,
            "title"       => "Projected Monthly Revenue",
            "valueField"  => "projected_revenue",
            'dashLength'  => 5,
            "type"        => "column",
            "clustered"   => false,
            "columnWidth" => 0.3,
            "balloonText" => '$[[value]] Projected Monthly Revenue'
        ]);

        $locations = Location::with( "listings", "listings.rates", "listings.stats", "stats" )->whereIn( "id", [ 1, 2, 3, 4 ] )->get();
        foreach ($locations as $location) {

            $stats = $location->stats;

            /* Dont include if stats have not been saved yet */
            if ( !$stats ) {
                continue;
            }

            $chartService->addData( [
                'label'             => $location->city . ", " . $location->state,
                "occupancy"         => $stats->percent_booked,
                "bed_price"         => $stats->price_per_bed,
                "projected_revenue" => $stats->projected_revenue,
            ]);
        }

        $this->assertNotEmpty($chartService->getChartJson());
    }
}
