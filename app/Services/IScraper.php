<?php

namespace App\Services;
use App\Location;
use App\Listing;

interface IScraper
{
    public function execute( Array $options );
}