<?php

namespace Tests\Feature;

use App\Services\ScraperService;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class BoxOfficeTest extends TestCase
{
    /**
     * A basic test example.
     * @group proxy
     * @return void
     */
    public function testBoxOffice()
    {
        $scraper = new \App\Services\TicketService();

        $scraper->fetchBoxOfficeListings(500, 1);
        $this->assertEquals( true, $scraper->state( 'box_logged_in' ) );

        print_r($scraper->getLog());
    }
}
