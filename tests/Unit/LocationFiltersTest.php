<?php

namespace Tests\Feature;

use App\Services\ScraperService;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class LocationFiltersTest extends TestCase
{
    /**
     * Test filtering location listing relations
     * @group locationfilters
     * @return void
     */
    public function testLocationFilters()
    {
        $location = \App\Location::where("city", "Park City")->with('listings')->first();
        $location->setSource("combined");
        $totalCount = $location->listings->count();

        $numOutliers = 0;
        $numCondos = 0;
        $numPotential = 0;
        foreach ($location->listings as $listing) {
            if ($listing->outlier) {
                $numOutliers++;
            }
            if ($listing->potential_outlier) {
                $numPotential++;
            }
            if ($listing->room_type != 'home') {
               $numCondos++;
            }
        }

        $this->assertGreaterThan(0, $numOutliers);
        $this->assertGreaterThan(0, $numCondos);
        $this->assertGreaterThan(0, $numPotential);
        $this->assertGreaterThan( 0, $location->listings->count() );

        $location->filterHomesOnly();
        $numOutliers = 0;
        $numCondos = 0;
        $numPotential = 0;
        foreach ($location->listings as $listing) {
            if ( $listing->outlier ) {
                $numOutliers++;
            }
            if ( $listing->potential_outlier ) {
                $numPotential++;
            }
            if ( $listing->room_type != 'home' ) {
                $numCondos++;
            }
        }

        $this->assertGreaterThan( 0, $numOutliers );
        $this->assertEquals( 0, $numCondos );
        $this->assertGreaterThan( 0, $numPotential );
        $this->assertGreaterThan( 0, $location->listings->count() );

        $location->filterNoOutliers();
        $numOutliers = 0;
        $numCondos = 0;
        $numPotential = 0;
        foreach ($location->listings as $listing) {
            if ( $listing->outlier ) {
                $numOutliers++;
            }
            if ( $listing->potential_outlier ) {
                $numPotential++;
            }
            if ( $listing->room_type != 'home' ) {
                $numCondos++;
            }
        }

        $this->assertEquals( 0, $numOutliers );
        $this->assertGreaterThan( 0, $numCondos );
        $this->assertGreaterThan( 0, $numPotential );
        $this->assertGreaterThan( 0, $location->listings->count() );

        $location->filterNoPotentialOutliers();
        $numOutliers = 0;
        $numCondos = 0;
        $numPotential = 0;
        foreach ($location->listings as $listing) {
            if ( $listing->outlier ) {
                $numOutliers++;
            }
            if ( $listing->potential_outlier ) {
                $numPotential++;
            }
            if ( $listing->room_type != 'home' ) {
                $numCondos++;
            }
        }

        $this->assertGreaterThanOrEqual(0, $numOutliers );
        $this->assertGreaterThan( 0, $numCondos );
        $this->assertEquals( 0, $numPotential );
        $this->assertGreaterThan( 0, $location->listings->count() );

        $location->filterByBeds([0,1], true);
        $this->assertLessThan( $totalCount, $location->listings->count() );
        echo  "\n" . $location->listings->count() . " listings found with 1 bed \n";

        $location->filterByBeds([2,3], true );
        $this->assertLessThan( $totalCount, $location->listings->count() );
        echo $location->listings->count() . " listings found with 2 to 3 beds \n";

        $location->filterByBeds([4,5,6], true );
        $this->assertLessThan( $totalCount, $location->listings->count() );
        echo $location->listings->count() . " listings found with 4 to 6 beds \n";

        $location->filterByBeds([7,8,9,10], true );
        $this->assertLessThan( $totalCount, $location->listings->count() );
        echo $location->listings->count() . " listings found with 7 to 10 beds \n";

        $location->filterByBeds([11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30], true );
        $this->assertLessThan( $totalCount, $location->listings->count() );
        echo $location->listings->count() . " listings found with 11+ beds \n";

        echo $totalCount . " total listings. \n";

    }

}
