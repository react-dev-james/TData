<?php
/**
 * Created by PhpStorm.
 * User: sungwhikim
 * Date: 30/07/2018
 * Time: 16:40
 */

namespace App\Models;

use Illuminate\Support\Facades\Log;


class RemoveOldListings
{
    public static function remove()
    {
        Log::info('//-- Start remove old listings --//');

        $startDate = \Carbon\Carbon::now();
        $startDate->startOfWeek();

        /* Remove all listings from the previous weeek */
        $listings = \App\Listing::whereDate('created_at','<',$startDate);
        Log::info("Found " .$listings->count() . " to soft delete.");
        \App\Listing::whereDate( 'created_at', '<', $startDate )->delete();

        /* Update first on sale date for all listings */
        $listings = \App\Listing::withTrashed()->with('sales')->get();
        $numUpdated = 0;
        Log::info("Updating on sale dates for " . $listings->count() . " listings.");
        foreach ($listings as $listing) {
            if ($listing->sales->count() > 0) {
                $listing->first_onsale_date = $listing->sales->first()->sale_date;
                $listing->save();
                $numUpdated++;
            }
        }
        Log::info("Updated on sale dates for " . $numUpdated . " listings.");

        /* Move any listings with a current onsale/presale into current listings */
        $listings = \App\Listing::onlyTrashed()->with( 'sales' )->get();
        $startDate = \Carbon\Carbon::now();
        $startDate->startOfWeek();
        $endDate = $startDate->copy()->addDays( 7 );
        $numCurrent = 0;
        foreach ($listings as $listing) {
            $sale = $listing->sales->first();
            if (!$sale) {
                continue;
            }

            if ($sale->sale_date->gte($startDate) && $sale->sale_date->lte($endDate)) {
                Log::info( "Listing " . $listing->id . " has a current onsale, moving it to current list." );
                Log::info( "Sale date is " . $sale->sale_date->toDateTimeString());
                $listing->restore();
                $numCurrent++;
            }
        }

        Log::info( "Moved  " . $numCurrent. " listings into current week." );
        Log::info('//-- End remove old listings --//');
    }
}