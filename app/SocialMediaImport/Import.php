<?php
/**
 * Created by PhpStorm.
 * User: sungwhikim
 * Date: 14/11/2018
 * Time: 15:44
 */

namespace App\SocialMediaImport;

use Illuminate\Support\Facades\DB;

class Import
{
    protected $max_rows;
    protected $update_days;

    public function run()
    {
        // determine which social media rows to get for this update

        // get end date for social media update
        $end_date = '2018-11-14';

        // get social media rows to update
        $social_medias = DB::table('social_medias')->where('updated_at', '<=', $end_date)->get();

        // update social media data
        foreach( $social_medias as $social_media )
        {
            // get the social media data from various sources

            // update only if we have data from each source
        }



        // write results to log
    }
}