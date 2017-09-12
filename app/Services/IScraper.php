<?php
/**
 * @desc Interface for vacation rental scrapers. (HomeAway, AirBnb, VRBO)
 * @package
 */

namespace App\Services;
use App\Location;
use App\Listing;

interface IScraper
{
    public function setLocation( Location $location );
    public function getLocation( $city, $state, $country = "United States" );
    public function addNewLocation( $city, $state, $page = 1, $numPages = 10, $throttle = 1, $country = "United States" );
    public function getListingDetails( Listing $listing );
}