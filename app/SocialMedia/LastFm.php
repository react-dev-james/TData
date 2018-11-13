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

        // send request
        //$response = \App\SocialMedia\Api::call($this->url, $parameters);

        $ch = curl_init("http://ws.audioscrobbler.com/2.0/?api_key=0a5b3500c2c9a42f64a977fafe8f143a&method=artist.search&format=json&artist=taylor+swift");

        curl_setopt($ch, CURLOPT_HEADER, 0);

        $response = curl_exec($ch);
        curl_close($ch);

echo($response);
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