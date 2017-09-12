<?php

namespace App\Services;

use \App\Location;
use \App\Listing;
use Illuminate\Support\Collection;

class ListingService extends ScraperService
{

    const MAX_BOOKED_DAYS_IN_ROW = 33;

    public function findDuplicateListings( Listing $listing )
    {

        /* Find listings within 2 mile radius */
        $radius = 0.3;
        $closeListings = Listing::with( "rates" )
            ->whereBetween( 'lat', [ $listing->lat - ( $radius * 0.018 ), $listing->lat + ( $radius * 0.018 ) ] )
            ->whereBetween( 'lng', [ $listing->lng - ( $radius * 0.018 ), $listing->lng + ( $radius * 0.018 ) ] )
            ->where( "source", "!=", $listing->source )
            ->where( "bedrooms",  $listing->bedrooms )
            ->get();

        $this->display( "Found " . $closeListings->count() . " listings close to " . $listing->id );

        /* Check booked day overlaps to determine % of overlap */
        $listing->load( "rates" );

        foreach ($closeListings as $closeListing) {
            $overlappedBookings = 0;
            $numNotOverlapped = 0;
            $available = 0;
            $notAvailable = 0;
            $totalBookingsChecked = 0;
            foreach ($listing->rates as $rate) {
                $closeRate = $closeListing->rates->first( function ( $value, $key ) use ( $rate ) {
                    return $value->date == $rate->date;
                } );

                if ( !$closeRate ) {
                    continue;
                }

                /* Track to establish variance */
                if ($rate->available == true) {
                    $available++;
                } else {
                    $notAvailable++;
                }

                $totalBookingsChecked++;
                if ( $closeRate->available == $rate->available ) {
                    $overlappedBookings++;
                } else {
                    $numNotOverlapped++;
                }

                /* Break early if no match will be found */
                if ($numNotOverlapped > 20 && $overlappedBookings < 100) {
                    //$this->display("Breaking early, bookings do not match");
                    continue 2;
                }
            }

            if ($totalBookingsChecked == 0) {
                continue;
            }

            $percentMatched = $overlappedBookings / $totalBookingsChecked * 100;
            if ( $listing->rates->count() > 100 && $percentMatched > 95 ) {

                if ( $available < 16 || $notAvailable < 16 ) {
                    $this->display("Rates match but not enough variance to be confident. ");
                } else {

                    /* Match has been found */
                    $listing->duplicates()->attach($closeListing->id, ['confidence' => round($percentMatched, 2)]);


                    $this->display( "Listing " . $listing->name . " and listing " . $closeListing->name . " have " . $overlappedBookings . " out of " . $totalBookingsChecked . " matching dates." );
                    $this->display( "Listing " . $listing->id . " has " . $listing->bedrooms . " rooms and " . $closeListing->id . " has " . $closeListing->bedrooms );
                }

            }
        }

        /* Check avg rates to see how close they are
        $avgRate = $listing->rates->average( "rate" );
        foreach ($closeListings as $closeListing) {
            $closeAvgRate = $closeListing->rates->average( "rate" );
            $difference = abs( $closeAvgRate - $avgRate );
            //$this->display( "Average rate difference of " . $difference . " for " . $listing->id . " and " . $closeListing->id );
        }
        */
    }

    /**
     * @param \Illuminate\Database\Eloquent\Collection $listings
     * @return array
     */
    public function identifyBlockedBookings( $listings )
    {

        $this->display( "Checking " . $listings->count() . " listings for blocked dates." );

        /**
         * @var \App\Listing $listing;
         */
        $adjustedListingIds = [];
        foreach ($listings as $listing) {
            $currentBlock = 1;
            $totalDays = 0;
            $curIndex = 1;
            $lastStatus = null;
            $bookingBlocks = [];
            /**
             * @var \App\Rate[] $rates
             */
            $rates = [];

            foreach ($listing->rates as $rate) {

                $totalDays++;
                $curIndex++;

                if ( $lastStatus === null ) {
                    $lastStatus = $rate->available;
                    $currentBlock++;
                    continue;
                }


                /* Need to check against rates count in case the rate status hasnt changed (meaning the bookingBlock won't have been saved */
                if ( $rate->available == $lastStatus && $curIndex != $listing->rates->count() ) {

                    $currentBlock++;
                    $rates[] = $rate;
                } else {
                    $count = $listing->rates->count();
                    if ( !$lastStatus ) {
                        $bookingBlocks[] = [
                            'booked'     => $currentBlock,
                            'total_days' => $totalDays,
                            'rates'      => $rates
                        ];
                    } else {
                        $bookingBlocks[] = [
                            'not_booked' => $currentBlock,
                            'total_days' => $totalDays,
                            'rates' => $rates
                        ];
                    }

                    $currentBlock = 1;
                    $rates = [];
                }

                $lastStatus = $rate->available;

            }

            // echo "Found " . count($bookingBlocks) . " blocks of bookings. \n";

            /* Check if any blocks have over max # of sequential days */
            foreach ($bookingBlocks as $block) {
                if (isset($block['booked']) && $block['booked'] > self::MAX_BOOKED_DAYS_IN_ROW) {
                    $this->display("Identified a continuous block of " . $block['booked'] . " bookings. Resetting availability & updating stats.");
                    foreach ($block['rates'] as $rate) {
                        $rate->available = true;
                        $rate->save();
                    }

                    $adjustedListingIds[] = [
                        'id' => $listing->id,
                        'num_rates' => count($block['rates'])
                    ];
                }
            }

        }

        return $adjustedListingIds;

        /**
         * Old blocked bookings algorithm
         * @var \App\Listing $listing
         */
        foreach ($listings as $listing) {

            $this->display( "Checking listing " . $listing->id . " with occupancy rate of " . $listing->stats->percent_booked . "%" );
            $extendedBookings = [];
            $concurrentDays = 0;
            $inBetweenDays = 0;
            $daysChecked = 0;
            $bookedInARow = 0;
            foreach ($listing->rates as $rate) {
                $daysChecked++;

                /* If the listing is not booked on this day we save the current booking block and reset so a new block is created */
                if ( $rate->available == false ) {
                    $concurrentDays++;
                    $bookedInARow++;
                } else {
                    if ( $concurrentDays > 1 ) {
                        $extendedBookings[] = [
                            'days_booked'     => $concurrentDays,
                            'days_not_booked' => $inBetweenDays,
                            'total_days'      => $daysChecked,
                            'booked_in_row'   => $bookedInARow,
                        ];
                        $inBetweenDays = 0;
                    }
                    $bookedInARow = 0;
                    $inBetweenDays++;
                    $concurrentDays = 0;
                }
            }

            if ( $concurrentDays > 1 ) {
                $extendedBookings[] = [
                    'days_booked'     => $concurrentDays,
                    'days_not_booked' => $inBetweenDays,
                    'total_days'      => $daysChecked,
                    'booked_in_row'   => $bookedInARow,
                ];
            }

            print_r( $extendedBookings );

        }

    }


}
