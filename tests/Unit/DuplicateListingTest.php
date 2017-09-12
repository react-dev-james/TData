<?php

namespace Tests\Feature;

use App\Services\ScraperService;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class DuplicateListingTest extends TestCase
{
    /**
     * Search for duplicate listings
     * @group duplicatelistings
     * @return void
     */
    public function testDuplicateListings()
    {

        $service = new \App\Services\ListingService();
        $listings = \App\Listing::where('source','homeaway')->whereHas('rates')->skip(0)->take(200)->get();

        echo "\n\nFound " . $listings->count() . " from homeaway with rates fetched. \n";

        foreach ($listings as $listing) {
            $service->findDuplicateListings($listing);
        }
        print_r( $service->getLog() );

    }

}