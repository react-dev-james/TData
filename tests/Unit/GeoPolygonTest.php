<?php

namespace Tests\Feature;

use App\Services\ScraperService;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class GeoPolygonTest extends TestCase
{
    /**
     * Test how many listings are within a given polygon
     * @group geo
     * @return void
     */
    public function testListingsInPolygon()
    {
        $polygon = [ "46.40756,-99.53613", "42.90816,-101.82129", "42.35854,-97.99805", "41.24477,-90.26367", "44.59047,-91.62598" ];
        $polygon = [ "42.00709,-111.26026", "41.99714,-111.27708", "41.99076,-111.25957", "41.99407,-111.24172", "42.0039,-111.24292"];
        $polygon = [ "40.73477,-111.29013", "40.66085,-111.44531", "40.5472,-111.3327", "40.54198,-111.20361", "40.58058,-111.1528", "40.65043,-111.13632" ];
        $polygon = [ "42.87596,-111.1377", "42.5207,-113.15918", "40.5472,-112.10449", "40.61395,-109.55566", "41.77131,-109.59961" ];
        $listings = \App\Listing::all();

        $geo = new \App\Services\GeoService();
        $numListings = 0;
        foreach ($listings as $listing) {
            $location = $geo->pointInPolygon($listing->lat . "," . $listing->lng, $polygon);
            if ($location == 'inside') {
                //echo $listing->name . " in " . $listing->city . " is " . $location . " polygon \n";
                $numListings++;
            }
        }

        $this->assertGreaterThan(0, $numListings);
        echo $numListings . " listings found in region.";

    }

}