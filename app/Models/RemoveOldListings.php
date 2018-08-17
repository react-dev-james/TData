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
        echo "//-- Start remove old listings --//\n";
        Log::info('//-- Start remove old listings --//');

        $startDate = \Carbon\Carbon::now();
        $startDate->startOfWeek();

        /* Remove all listings from the previous weeek */
        $listings = \App\Listing::whereDate('created_at','<',$startDate);
        $listings->delete();

        echo "Found " .$listings->count() . " to soft delete.\n";
        Log::info("Found " .$listings->count() . " to soft delete.");

        /* Update first on sale date for all listings */
        //$result = DB::update('UPDATE listings join sales on listings.id = sales.listing_id set listings.first_onsale_date = sales.sale_date');


        /* Update first on sale date for all listings */
        $listings = \App\Listing::withTrashed()->with('sales')->get();

        echo "Updating on sale dates for " . $listings->count() . " listings.\n";
        Log::info("Updating on sale dates for " . $listings->count() . " listings.");

        $numUpdated = 0;
        foreach ($listings as $listing) {
            if ($listing->sales->count() > 0) {
                $listing->first_onsale_date = $listing->sales->first()->sale_date;
                $listing->save();
                $numUpdated++;
            }
        }

        echo "Updated on sale dates for " . $numUpdated . " listings.\n";
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
                $listing->restore();
                $numCurrent++;
            }
        }

        echo "Moved  " . $numCurrent. " listings into current week.\n";
        echo "//-- End remove old listings --//\n";
        Log::info("Moved  " . $numCurrent. " listings into current week.");
        Log::info('//-- End remove old listings --//');
    }
}