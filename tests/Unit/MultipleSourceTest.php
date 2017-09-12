<?php

namespace Tests\Feature;

use App\Services\ScraperService;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class MultipleSourceTest extends TestCase
{
    /**
     * Test loading airbnb and homeaway listings
     * @group sources
     * @return void
     */
    public function testMultipleListingSources()
    {
        $location = \App\Location::where("city", "Park City")->with('listings')->first();

        $numAir = 0;
        $numHome = 0;
        foreach ($location->listings as $listing) {
            if ($listing->source == 'airbnb') {
                $numAir++;
            }
            if ($listing->source == 'homeaway') {
                $numHome++;
            }
        }

        $this->assertGreaterThan(0, $numAir);
        $this->assertGreaterThan(0, $numHome);

    }

    /**
     * @group sources
     */
    public function testSingleListingSource()
    {
        /* Test loading only airbnb listings */
        $location = \App\Location::where( "city", "Park City" )->with( ['listings' => function($query) {
            $query->where('source','airbnb');
        }] )->first();

        $numAir = 0;
        $numHome = 0;
        foreach ($location->listings as $listing) {
            if ( $listing->source == 'airbnb' ) {
                $numAir++;
            }
            if ( $listing->source == 'homeaway' ) {
                $numHome++;
            }
        }

        $this->assertGreaterThan( 0, $numAir );
        $this->assertEquals( 0, $numHome );

        /* Test loading only homeaway listings */
        $location = \App\Location::where( "city", "Park City" )->with( [
            'listings' => function ( $query ) {
                $query->where( 'source', 'homeaway' );
            }
        ] )->first();

        $numAir = 0;
        $numHome = 0;
        foreach ($location->listings as $listing) {
            if ( $listing->source == 'airbnb' ) {
                $numAir++;
            }
            if ( $listing->source == 'homeaway' ) {
                $numHome++;
            }
        }

        $this->assertGreaterThan( 0, $numHome );
        $this->assertEquals( 0, $numAir );

    }



    /**
     * Test loading all listings then filtering by one source after model is loaded
     * @group sources
     */
    public function testMultipleAndSingleSource()
    {
        $location = \App\Location::where( "city", "Park City" )->with( 'listings' )->first();

        $numAir = 0;
        $numHome = 0;
        foreach ($location->listings as $listing) {
            if ( $listing->source == 'airbnb' ) {
                $numAir++;
            }
            if ( $listing->source == 'homeaway' ) {
                $numHome++;
            }
        }

        $this->assertGreaterThan( 0, $numAir );
        $this->assertGreaterThan( 0, $numHome );

        /* Filter only airbnb listings */
        $location->load( [
            'listings' => function ( $query ) {
                $query->where( 'source', 'airbnb' );
            }
        ] );

        $numAir = 0;
        $numHome = 0;
        foreach ($location->listings as $listing) {
            if ( $listing->source == 'airbnb' ) {
                $numAir++;
            }
            if ( $listing->source == 'homeaway' ) {
                $numHome++;
            }
        }

        $this->assertGreaterThan( 0, $numAir );
        $this->assertEquals( 0, $numHome );

        /* Filter only home listings */
        $location->load( [
            'listings' => function ( $query ) {
                $query->where( 'source', 'homeaway' );
            }
        ] );

        $numAir = 0;
        $numHome = 0;
        foreach ($location->listings as $listing) {
            if ( $listing->source == 'airbnb' ) {
                $numAir++;
            }
            if ( $listing->source == 'homeaway' ) {
                $numHome++;
            }
        }

        $this->assertGreaterThan( 0, $numHome );
        $this->assertEquals( 0, $numAir );

        /* Load all listings again */
        $location->load( 'listings' );

        $numAir = 0;
        $numHome = 0;
        foreach ($location->listings as $listing) {
            if ( $listing->source == 'airbnb' ) {
                $numAir++;
            }
            if ( $listing->source == 'homeaway' ) {
                $numHome++;
            }
        }

        $this->assertGreaterThan( 0, $numAir );
        $this->assertGreaterThan( 0, $numHome );

    }

    /**
     * Test loading all listings then filtering by one source after model is loaded
     * @group sources
     */
    public function testDynamicSource()
    {
        $location = \App\Location::where( "city", "Park City" )->with( 'listings' )->first();

        $numAir = 0;
        $numHome = 0;
        foreach ($location->listings as $listing) {
            if ( $listing->source == 'airbnb' ) {
                $numAir++;
            }
            if ( $listing->source == 'homeaway' ) {
                $numHome++;
            }
        }

        $this->assertGreaterThan( 0, $numAir );
        $this->assertGreaterThan( 0, $numHome );

        /* Filter only airbnb listings */
        $location->setSource("airbnb");

        $numAir = 0;
        $numHome = 0;
        foreach ($location->listings as $listing) {
            if ( $listing->source == 'airbnb' ) {
                $numAir++;
            }
            if ( $listing->source == 'homeaway' ) {
                $numHome++;
            }
        }

        $this->assertGreaterThan( 0, $numAir );
        $this->assertEquals( 0, $numHome );

        /* Filter only home listings */
        $location->setSource("homeaway");

        $numAir = 0;
        $numHome = 0;
        foreach ($location->listings as $listing) {
            if ( $listing->source == 'airbnb' ) {
                $numAir++;
            }
            if ( $listing->source == 'homeaway' ) {
                $numHome++;
            }
        }

        $this->assertGreaterThan( 0, $numHome );
        $this->assertEquals( 0, $numAir );

        /* Load all listings again */
        $location->setSource("combined");

        $numAir = 0;
        $numHome = 0;
        foreach ($location->listings as $listing) {
            if ( $listing->source == 'airbnb' ) {
                $numAir++;
            }
            if ( $listing->source == 'homeaway' ) {
                $numHome++;
            }
        }

        $this->assertGreaterThan( 0, $numAir );
        $this->assertGreaterThan( 0, $numHome );

    }
}
