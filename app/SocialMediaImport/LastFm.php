<?php
/**
 * Created by PhpStorm.
 * User: sungwhikim
 * Date: 08/11/2018
 * Time: 16:16
 */

namespace App\SocialMedia;

use App\SocialMedia\Api;
use Illuminate\Support\Facades\Log;

class LastFm
{
    protected $api_key;
    protected $url = 'http://ws.audioscrobbler.com/2.0/';

    public function __construct($api_key)
    {
        $this->api_key = $api_key;
    }

    public function getArtistData($artist_name)
    {
        // set the parameters
        $parameters = [
            'api_key' => $this->api_key,
            'method' => 'artist.search',
            'format' => 'json',
            'artist' => $artist_name,
        ];

        // set the querystring
        $query_string = http_build_query($parameters);

        // init request
        $ch = curl_init($this->url . '?' . $query_string);

        // set options
        curl_setopt($ch, CURLOPT_TIMEOUT, 180);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        // get response
        $response = curl_exec($ch);

        //check for curl error
        if( curl_error($ch) ) {
            Log::error('*** Curl Error ***');
            Log::error('Request Route: ' . $this->url . $query_string);
            Log::error('Curl Error: ' . curl_error($ch));

            $response = null;
        }

        curl_close($ch);

        // convert data
        $data = json_decode($response);

        /* check response */
        // found artist
        if( isset($data->results->artistmatches->artist) && count($data->results->artistmatches->artist) > 0 ) {
            return $data->results->artistmatches->artist[0];
        }

        // artist not found
        else {
            return false;
        }
    }
}