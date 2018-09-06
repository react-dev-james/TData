<?php
/**
 * Created by PhpStorm.
 * User: sungwhikim
 * Date: 10/08/2018
 * Time: 16:15
 */

namespace App\TicketMaster;

use Illuminate\Support\Facades\Log;


class TicketMaster
{
    private $api_key;
    public $calls_made = 0;

    public function __construct($api_key)
    {
        $this->api_key = $api_key;
    }

    public function call($route, $params = [])
    {
        //init curl object
        $ch = curl_init();

        //set header options
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'get');
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $request_headers = [
            "Accept: application/json",
        ];

        //set header
        curl_setopt($ch, CURLOPT_HTTPHEADER, $request_headers);

        // add api key
        $params['apikey'] = $this->api_key;

        //set url
        curl_setopt($ch, CURLOPT_URL, 'https://app.ticketmaster.com' . $route . '?' . http_build_query($params));

        //run request
        $response = curl_exec($ch);

        //check for curl error
        if( curl_error($ch) ) {
            Log::error('*** Curl Error ***');
            Log::error('Request Route: ' . $route);
            Log::error('Curl Error: ' . curl_error($ch));

            $response = null;
        }

        /* debug */
        // update calls made
        $this->calls_made++;

        // get full call route
        if( env('API_DEBUG') ) {
            echo 'https://app.ticketmaster.com' . $route . '?' . http_build_query($params);
            Log::info('https://app.ticketmaster.com' . $route . '?' . http_build_query($params));
        }

        return $response;
    }

    /**
     * Event search call
     *
     * @param $search_criteria
     *
     * @return array
     */
    public function eventSearch($search_criteria)
    {
        // make call
        $response = $this->call('/discovery/v2/events.json', $search_criteria);

        // decode data
        $data = json_decode($response);

        // return an empty array if nothing was found
        return $data;
    }

    /**
     * Gets the event offers
     *
     * @param $event_id
     *
     * @return mixed
     */
    public function getEventOffers($event_id)
    {
        // make call
        $response = $this->call("/commerce/v2/events/$event_id/offers.json");

        // decode data
        $data = json_decode($response);

        // debug
        if( env('API_DEBUG') && !isset($data->offers[0]) ) {
            echo "--- offer not found ---\n";
            echo "/commerce/v2/events/$event_id/offers.json\n";
        }

        return $data;
    }
}