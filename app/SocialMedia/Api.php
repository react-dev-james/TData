<?php
namespace App\SocialMedia;

use Illuminate\Support\Facades\Log;

class Api
{

    /**
     * Main Curl call
     *
     * @param       $route
     * @param array $params
     * @param array $headers
     *
     * @return mixed|null
     */
    public static function call($route, $params = [], $headers = [])
    {
        //init curl object
        $ch = curl_init();

        //set header options
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'get');
        curl_setopt($ch, CURLOPT_TIMEOUT, 300);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        //curl_setopt($ch, CURLOPT_ENCODING ,"");

        $request_headers = [
            "Accept: application/json",
            "Content-type: text/html; charset=utf-8",
        ];

        $request_headers = array_merge($request_headers, $headers);

        //set header
        curl_setopt($ch, CURLOPT_HTTPHEADER, $request_headers);

        // set query string
        $query_string = count($params) > 0 ? '?' . http_build_query($params) : '';

        //set url
        curl_setopt($ch, CURLOPT_URL, $route . $query_string);

        //run request
        $response = curl_exec($ch);

        //check for curl error
        if( curl_error($ch) ) {
            Log::error('*** Curl Error ***');
            Log::error('Request Route: ' . $route . $query_string);
            Log::error('Curl Error: ' . curl_error($ch));

            $response = null;
        }

        curl_close($ch);

        return $response;
    }
}