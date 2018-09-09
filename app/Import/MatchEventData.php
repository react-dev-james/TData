<?php
/**
 * Created by PhpStorm.
 * User: sungwhikim
 * Date: 30/07/2018
 * Time: 15:53
 */

namespace App\Import;

use Illuminate\Support\Facades\Log;


class MatchEventData
{
    /* This has been moved to /config/api.php */
    //protected $exclusions = [];

    public function match()
    {
        $data = \App\DataMaster::all();
        $listings = \App\Listing::all();

        echo "------- start match: check lookup table --------\n";
        Log::info('------- start match: check lookup table --------');

        /* Check lookups table first */
        foreach ($listings as $listing) {

            /* If listing has match, skip it */
            if ($listing->data) {
                continue;
            }

            /* Check lookups table */
            $lookup = \App\EventLookup::where( "event_name", $listing->event_name )->orderBy("is_auto",'ASC')->orderBy('confidence','DESC')->first();
            if ( $lookup ) {
                $dataLookup = \App\DataMaster::where("category", $lookup->match_name)->first();
                if ($dataLookup) {
                    $listing->performer = $dataLookup->category;
                    $listing->data_master_id = $dataLookup->id;
                    $listing->confidence = 100;
                    $listing->save();

                    $listing->calcRoi($dataLookup);
                }
            }

            /* Exclusions */
            if ($listing->category == 'Sports') {
                $listing->forceDelete();
                continue;
            }

            /* Exclusion list */
            $exclusions = $exclusions = config('api.event_exclusions');
            foreach ($exclusions as $exclusion) {
                if (stristr($listing->event_name, $exclusion) !== false) {
                    $listing->forceDelete();
                    break;
                }
            }

        }

        echo "------- end match: check lookup table --------\n";
        Log::info('------- end match: check lookup table --------');

        echo "------- start match: based on event data names --------\n";
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
                $confidence = round(max(0,($similarity / max(1, (strlen($listing->event_name) - strlen($item->category)))) * 100 - $distance));

                if ($confidence >= 5 || strtolower($item->category) == strtolower($listing->event_name)) {
                    $numMatches++;
                    //Log::info( "Matching data found for " . $listing->event_name . " matched with " . $item->category );
                    //Log::info( "Distance: " . $distance . " Similarity: " . $similarity );
                    //Log::info( "Confidence: " . $confidence );
                    //echo "Matching data found for " . $listing->event_name . " matched with " . $item->category ;
                    //echo "Distance: " . $distance . " Similarity: " . $similarity;
                    //echo "Confidence: " . $confidence;

                    $this->saveNewMatch($listing, $item, $confidence);
                }
            }
        }

        echo  "Found Matches: " . $numMatches . "\n";
        Log::info( "Found Matches: " . $numMatches );

        echo "------- end match: based on event data names --------\n";
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
                'confidence' => round(min(100, $confidence * 3))
            ]);

        $listing->performer = $data->category;
        $listing->data_master_id = $data->id;
        $listing->confidence = min( 100, $confidence * 3 );
        $listing->save();

        /* Recalc ROI */
        $listing->calcRoi($data);
    }

    public function checkLookups() {
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

                $numListings++;
                $otherListing->performer = $lookup->match_name;
                $otherListing->data_master_id = $data->id;
                $otherListing->confidence = 100;
                $otherListing->save();

                /* Recalc ROI */
                $otherListing->calcRoi($data);
            }
        }

        echo "Found " . $numListings . " events that matched with lookups.\n";
        Log::info("Found " . $numListings . " events that matched with lookups.");

    }
}
