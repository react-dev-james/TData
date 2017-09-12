<?php

namespace Tests\Feature;

use App\Services\ScraperService;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class AirBnbProxyTest extends TestCase
{
    /**
     * A basic test example.
     * @group proxy
     * @return void
     */
    public function testAirBnbProxies()
    {
        $scraper = new \App\Services\AirBnbService();
        $scraper->useCrawlera();
        $scraper->getListingDetails(\App\Listing::find(1)->first());
        $this->assertContains("Listing updated successfully.", $scraper->getLog());
    }
}
