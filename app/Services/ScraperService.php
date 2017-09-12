<?php

namespace App\Services;

use GuzzleHttp\Client;
use Symfony\Component\DomCrawler\Crawler;

class ScraperService
{
    protected $startTime;
    protected $client;
    protected $lastResponse;
    protected $redirects;
    protected $crawler;
    protected $proxy;
    protected $proxies;
    protected $log;

    public function __construct( Array $clientOptions = [ 'verify' => false ] )
    {
        $this->client = new Client( $clientOptions );
        //$this->cralwer = new Crawler();
        $this->proxies = \App\Proxy::where("status","active")->get();
        $this->redirects = [];
    }

    public function getClient()
    {
        return $this->client;
    }

    public function getCrawler()
    {
        return $this->crawler;
    }

    public function getResponse()
    {
        return $this->lastResponse;
    }

    public function getLog() {
        if (!is_array($this->log)) {
            $this->log = [];
        }
        return $this->log;
    }

    public function clearLog(  )
    {
        $this->log = [];
        return $this;
    }

    public function setRandomProxy() {
        $proxy = $this->proxies->random();
        $this->proxy = $proxy->proxy;
        return $this;
    }

    public function setProxy($proxy)
    {
        $this->proxy = $proxy;
    }

    public function useCrawlera() {
        $this->proxy = 'http://d1980ba552e14300be03b2090fca136a:@proxy.crawlera.com:8010';
        return $this;
    }

    protected function elapsed()
    {
        $this->display( time() - $this->startTime . " seconds elapsed " );
        return $this;
    }

    public function display( $message )
    {
        $this->log[] = $message;
        echo $message . "\n";
        return $this;
    }

    protected function has( $string, $wait = 5 )
    {
        $source = $this->lastResponse->getBody();
        if ( stristr( $source, $string ) === false ) {
            if ( $wait ) {
                sleep( $wait );
                return $this->assert( $string, false );
            } else {
                return false;
            }
        }

        return true;
    }

    protected function assert( $string, $wait = 5 )
    {

        $source = $this->lastResponse->getBody();
        if ( stristr( $source, $string ) === false ) {
            if ( $wait ) {
                sleep( $wait );
                return $this->assert( $string, false );
            } else {
                //$this->driver->takeScreenshot( base_path( "storage/app/webdriver/" ) . 'assert_failed_' . str_slug( $string ) . '.png' );
                file_put_contents( base_path( "storage/app/webdriver/" ) . 'assert_failed_' . str_slug( $string ) . '.html', $source );
                return false;
            }
        }

        return true;
    }

    protected function save($fileName, $data = '') {
        if (empty($data)) {
            $data = $this->lastResponse->getBody();
        }

        file_put_contents( base_path( "storage/app/webdriver/" ) . $fileName, $data );
        return $this;
    }

    protected function post( $url, $params = [], Array $headers = [] )
    {

        $options = [
            'timeout'         => 90,
            'headers'         => array_merge( $headers, [
                'User-Agent'  => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/56.0.2924.87 Safari/537.36',
            ] ),
            'allow_redirects' => [
                'track_redirects' => true
            ],
            "curl"            => [
                CURLOPT_TIMEOUT        => 0,
                CURLOPT_TIMEOUT_MS     => 0,
                CURLOPT_CONNECTTIMEOUT => 0,
            ]
        ];

        if (is_array($params)) {
            $options['form_params'] = $params;
        } else {
            $options['body'] = $params;
        }

        if ( !empty( $this->proxy ) ) {
            $options['proxy'] = $this->proxy;
        }

        try {
            $this->redirects = [];
            $this->lastResponse = $this->client->request( "POST", urldecode( $url ), $options );
            $this->redirects = $this->lastResponse->getHeader( \GuzzleHttp\RedirectMiddleware::HISTORY_HEADER );
        } catch ( Exception $e ) {
            return false;
        }

        return (string)$this->lastResponse->getBody();
    }

    protected function get( $url, Array $params = [] )
    {

        $options = [
                'timeout' => 90,
                'headers' => [
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/56.0.2924.87 Safari/537.36',
                ],
                'allow_redirects' => [
                    'track_redirects' => true
                ],
                "curl"    => [
                    CURLOPT_TIMEOUT        => 0,
                    CURLOPT_TIMEOUT_MS     => 0,
                    CURLOPT_CONNECTTIMEOUT => 0,
                ]
            ];

        if (!empty($this->proxy)) {
            $options['proxy'] = $this->proxy;
        }

        try {
            $this->redirects = [];
            $this->lastResponse = $this->client->request( "GET", urldecode( $url ), $options);
            $this->redirects = $this->lastResponse->getHeader( \GuzzleHttp\RedirectMiddleware::HISTORY_HEADER );
        } catch ( Exception $e ) {
            return false;
        }

        return (string) $this->lastResponse->getBody();
    }

    protected function decode( $response ) {
        return @json_decode($response, true);
    }

    public function checkProxy(  )
    {
        $this->get( 'http://www.whatismyip.org' );
        list( $userPass, $ipPort ) = explode( "@", $this->proxy );
        list ($ip, $port) = explode(":", $ipPort);
        return $this->assert( $ip );
    }

    protected function formatUrl( $url, Array $options )
    {
        foreach ($options as $key => $val) {
            $url = str_replace( "{" . $key . "}", $val, $url );
        }

        return $url;
    }
}
