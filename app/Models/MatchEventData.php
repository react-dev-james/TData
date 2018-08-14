<?php
/**
 * Created by PhpStorm.
 * User: sungwhikim
 * Date: 30/07/2018
 * Time: 15:53
 */

namespace App\Models;

use Illuminate\Support\Facades\Log;


class MatchEventData
{
    protected $exclusions = [
        'fast lane',
        'express lane',
        'club and parking upgrade',
        'vs.',
        'v.',
        'Lexus Lounge',
        'Preshow Experience',
        'Harlem Globetrotters',
        'Magic Pass',
        'Skip the Line',
        'Rock The Lounge',
        'Pre Show Dinner',
        'VIP Upgrade',
        'Vibe Room',
        'Retro Film Series',
        'Premium Box Seat',
        'Shore Club Cabanas',
        'Fastlane',
        'Lawn Chair Rental',
        'Club Upgrade',
        'VIP Club Access',
        'Starplex Pavilion Tailgate Club',
        'Tailgate Experience',
        'Preshow Reception',
        'Hollywood Casino Shuttle',
        'Park & Ride',
        'Market Shed Buffet',
        'Preferred Lawn Upgrade',
        'VIP Club Access',
        'VIP Lounge',
        'VIP Lounge',
        'The Green Room',
        'Lonestar Cabana',
        'River Garden Deck',
        'Pre-show Dinner',
        'River Garden Deck',
        'Early Entry',
        'Pre Concert Dinner',
        'Carrier Comfort Zone',
        'Fast Lane Access',
        'Lawn Chair Rental',
        'Reserved Lawn',
        'Hilltop Cabanas',
        'Flight Deck:',
        'TIFF Bell Lightbox',
        'Fast Lane',
        'Tribute',
        'Mini Club Access',
        'Preferred Viewing Area',
        'Meet & Greet',
        'Meet and Greet',
        'Lawn Chair',
        'Meal Voucher',
        'Party Deck',
        'Premium Box',
        'Box Seats',
        'Live Nation Blanket',
        'NOT A CONCERT TICKET'

    ];

    public function match()
    {
        $data = \App\DataMaster::all();
        $listings = \App\Listing::all();

        Log::info('------- start match: check lookup table --------');

        /* Check lookups table first */
        foreach ($listings as $listing) {

            /* If listing has match, skip it */
            if ($listing->data->count() > 0) {
                continue;
            }

            /* Check lookups table */
            $lookup = \App\EventLookup::where( "event_name", $listing->event_name )->orderBy("is_auto",'ASC')->orderBy('confidence','DESC')->first();
            if ( $lookup ) {
                $dataLookup = \App\DataMaster::where("category", $lookup->match_name)->first();
                if ($dataLookup) {

                    Log::info("Manual lookup match found for" . $listing->event_name);
                    $listing->performer = $dataLookup->category;
                    $listing->save();

                    /* Create new entry in the listing_data pivot table */
                    $listing->data()->sync( [ $dataLookup->id ], [ 'confidence' => 100 ] );
                    //$listing->fresh();
                    $listing->calcRoi($dataLookup);
                }
            }

            /* Exclusions */
            if ($listing->category == 'Sports') {
                $listing->forceDelete();
                continue;
            }

            /* Exclusion list */
            foreach ($this->exclusions as $exclusion) {
                if (stristr($listing->event_name, $exclusion) !== false) {
                    $listing->forceDelete();
                    break;
                }
            }

        }

        Log::info('------- end match: check lookup table --------');

        Log::info('------- start match: based on event data names --------');

        /* Check for matches based on event data names */
        $numMatches = 0;
        foreach ($data as $item) {

            $listings = \App\Listing::where("event_name",$item->category)->orWhere( "event_name", 'like', '%' . $item->category . "%" )->get();

            foreach ($listings as $listing) {

                if ($listing->status == 'excluded') {
                    continue;
                }

                /* Skip listings with matching 100% confidence lookups */
                $lookup = \App\EventLookup::where( "event_name", $listing->event_name )->orderBy( "is_auto", 'ASC' )->orderBy( 'confidence', 'DESC' )->first();
                if ($lookup && $lookup->confidence >= 100) {
                    continue;
                }

                /* Verify match */
                if ( strlen( $item->category ) <= 5 && strtolower($item->category) != strtolower($listing->event_name)) {
                    echo "X";
                    continue;
                }

                /* Calculate string similarity */
                $distance = levenshtein(strtolower($item->category), strtolower($listing->event_name));
                $similarity = similar_text( strtolower($item->category), strtolower($listing->event_name));
                $confidence = max(0,($similarity / max(1, (strlen($listing->event_name) - strlen($item->category)))) * 100 - $distance);

                if ($confidence >= 5 || strtolower($item->category) == strtolower($listing->event_name)) {
                    $numMatches++;
                    //Log::info( "Matching data found for " . $listing->event_name . " matched with " . $item->category );
                    //Log::info( "Distance: " . $distance . " Similarity: " . $similarity );
                    //Log::info( "Confidence: " . $confidence );
                    echo "Matching data found for " . $listing->event_name . " matched with " . $item->category;
                    echo "Distance: " . $distance . " Similarity: " . $similarity;
                    echo "Confidence: " . $confidence;

                    $this->saveNewMatch($listing, $item, $confidence);
                }
            }
        }

        Log::info( "Found Matches: " . $numMatches );

        Log::info('------- end match: based on event data names --------');

        $this->checkLookups();
    }

    public function saveNewMatch(\App\Listing $listing, \App\DataMaster $data, $confidence = 100)
    {
        /* Create new lookup in the lookups table */
        \App\EventLookup::firstOrCreate([ 'event_name' => $listing->event_name ],
            [
                'match_name' => $data->category,
                'event_slug' => str_slug($listing->event_name),
                'match_slug' => str_slug($data->category),
                'confidence' => min(100, $confidence * 3)
            ]);

        $listing->performer = $data->category;
        $listing->save();

        /* Create new entry in the listing_data pivot table */
        $listing->data()->sync([$data->id], ['confidence' => min( 100, $confidence * 3 ) ]);

        /* Recalc ROI */
        //$listing->fresh();
        $listing->calcRoi($data);
    }

    public function checkLookups() {
        Log::info('------- start match: checkLookups --------');

        /* Check lookups table for other matching listings */
        $lookups = \App\EventLookup::all();
        $numListings = 0;
        foreach ($lookups as $lookup) {
            $data = \App\DataMaster::where("category", $lookup->match_name)->first();
            if (!$data) {
                continue;
            }

            $listings = \App\Listing::where( "event_name", $lookup->event_name )->with('data')->get();
            foreach ($listings as $otherListing) {
                /* If listing has match, skip it */
                if ( $otherListing->data->count() > 0) {
                    $listingData = $otherListing->data->first();
                    if ($listingData->pivot->confidence >= 100) {
                        continue;
                    }
                }

                //$this->info("Matching new event based on lookups.");

                $numListings++;
                $otherListing->performer = $lookup->match_name;
                $otherListing->save();

                /* Create new entry in the listing_data pivot table */
                $otherListing->data()->sync( [ $data->id => [ 'confidence' => 100 ] ] );

                /* Recalc ROI */
                //$otherListing->fresh();
                Log::info('-*-* checkloopup before calcRoi -*-*-');
                Log::info('** data id is:' . $data->id);
                $otherListing->calcRoi($data);
            }
        }

        Log::info("Found " . $numListings . " events that matched with lookups.");
        Log::info('------- end match: checkLookups --------');

    }
}
