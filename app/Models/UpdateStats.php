<?php
/**
 * Created by PhpStorm.
 * User: sungwhikim
 * Date: 07/08/2018
 * Time: 14:20
 */

namespace App\Models;

use Illuminate\Support\Facades\Log;


class UpdateStats
{

    public static function update()
    {
        $listings = \App\Listing::get();

        foreach ($listings as $listing) {

            /* Calculate ROI for listing */
            try {
                $data = $listing->data->first();

                if ( !$data ) {
                    Log::error('/** Data object not found in UpdateStats.php */');
                    continue;
                }

                $listing->calcRoi($data);
                //$listing->updateSoldPerEvent();
                //$listing->updateWeightedSold();
            } catch (\Exception $e) {
                Log::error($e->getMessage());
                Log::error($e->getTraceAsString());
            }
        }
    }
}