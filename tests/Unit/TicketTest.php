<?php

namespace Tests\Feature;

use App\Services\ScraperService;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class TicketTest extends TestCase
{
    /**
     * Test ticketdata.com login & searching
     * @group proxy
     * @return void
     */
    public function testTicketDataLogin()
    {
        $scraper = new \App\Services\TicketService();
        $scraper->fetchTicketDataListings();
        $this->assertEquals(true, $scraper->state('ticket_logged_in'));

        print_r($scraper->getLog());
    }
}
