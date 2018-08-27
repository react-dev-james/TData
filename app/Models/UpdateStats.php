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
        $listings = \App\Listing::whereNotNull('data_master_id')->with('data')->get();

        foreach ($listings as $listing) {

            /* Calculate ROI for listing */
            $listing->calcRoi($listing->data);
        }
    }
}