<?php

namespace Tests\Feature;

use App\Services\ScraperService;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ZillowProxyTest extends TestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */
    public function testZillowProxies()
    {
        $location = \App\Location::where( "id", 5 )->first();
        $scraper = new \App\Services\ZillowService();
        $scraper->useCrawlera();

        $scraper->setLocation($location);
        $listings = $scraper->getListings(1, 1, 5);
        $this->assertGreaterThan(0, $listings->count());

        foreach ($listings as $listing) {
            $this->assertNotEmpty($listing['address']);
            $this->assertNotEmpty($listing['lat']);
            $this->assertNotEmpty($listing['lng']);
        }

    }
}
