<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use \Facebook\WebDriver\Remote\RemoteWebDriver;
use \Facebook\WebDriver\Remote\WebDriverCapabilityType;
use \Facebook\WebDriver\Remote\WebDriverBrowserType;
use \Facebook\WebDriver\WebDriverDimension;
use \Facebook\WebDriver\WebDriverCapabilities;
use \Facebook\WebDriver\WebDriverBy as By;
use GuzzleHttp\Client;

class PhantomCommand extends Command
{
    protected $startTime;

    /**
     * @var RemoteWebDriver
     */
    protected $driver;
    protected $driverBin;
    protected $driverPort;
    protected $driverPid;
    protected $driverProcess;
    protected $driverPipes;
    protected $client;
    protected $lastAssert;

    public function __construct()
    {
        $this->startTime = time();
        $this->driverBin = base_path("bin/phantomjs");
        $this->driverPort = rand( 8100, 9100 );
        $this->client = new Client(
            [
                'verify' => false
            ]
        );
        parent::__construct();
    }

    protected function elapsed()
    {
        return " " . time() - $this->startTime . " seconds elapsed ";
    }

    protected function display($message) {
        echo $message . "\n";
        return $this;
    }

    protected function startPhantom( $proxy = false )
    {
        if ( !empty( $this->driverPid ) || is_resource( $this->driverProcess ) ) {
            $this->killPhantom();
        }

        $command = "exec " . $this->driverBin . " --webdriver=127.0.0.1:" . $this->driverPort;
        if ( !empty( $proxy ) ) {
            $command .= " --proxy=" . $proxy . " --proxy-type=socks5";
        }
        $command .= ' > /dev/null 2>&1 &';

        \Log::debug( "Starting Phantom: " . $command . "\n");

        $descriptors = array(
            0 => array( "pipe", "r" ),
            1 => array( "pipe", "w" ),
            2 => array( "file", base_path("storage/app/webdriver/phantom.log"), "a" )
        );

        $this->driverPipes = [];
        $this->driverProcess = proc_open($command, $descriptors, $this->driverPipes);

        return $this;
    }

    protected function confirmPhantom() {
        $status = proc_get_status ( $this->driverProcess );

        if ($status['running']) {
            $this->driverPid = $status['pid'] + 1;
            $this->display( "Phantom started with PID of " . $this->driverPid );
            return true;
        } else {
            $this->display( "Failed to start phantom" );
            return false;
        }
    }

    protected function killPhantom()
    {

        if ( $this->driver instanceof RemoteWebDriver ) {
            $this->driver->quit();
        }

        if (empty($this->driverPid) && !is_resource($this->driverProcess)) {
            $this->display("No process to kill.");
            return true;
        }

        $this->display("Killing phantom.");
        $this->display( "Killing phantom, PID: " . $this->driverPid );
        proc_close($this->driverProcess);
        exec("kill -9 " . $this->driverPid);
        return true;
    }

    protected function createDriver( $userAgent = 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/54.0.2840.99 Safari/537.36' )
    {
        sleep(1);
        $host = '127.0.0.1:' . $this->driverPort;
        $capabilities = array(
            WebDriverCapabilityType::BROWSER_NAME => 'phantomjs',
            'phantomjs.page.settings.userAgent'   => $userAgent,
        );
        $this->driver = RemoteWebDriver::create( $host, $capabilities, 40 * 1000, 40 * 1000 );
        $window = new WebDriverDimension( 1920,1080 );
        $this->driver->manage()->window()->setSize($window);
        return $this->driver;

    }

    protected function has( $string, $wait = 5 )
    {
        $source = $this->driver->getPageSource();
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

        $this->lastAssert = $string;
        $source = $this->driver->getPageSource();
        if ( stristr( $source, $string ) === false ) {
            if ( $wait ) {
                sleep( $wait );
                return $this->assert( $string, false );
            } else {
                $this->driver->takeScreenshot( base_path("storage/app/webdriver/") . 'assert_failed_' . str_slug( $string ) . '.png' );
                file_put_contents( base_path( "storage/app/webdriver/" ) . 'assert_failed_' . str_slug( $string ) . '.html', $source );
                return false;
            }
        }

        return true;
    }

    protected function snap( $name )
    {
        $this->driver->takeScreenshot( base_path( "storage/app/webdriver/" ) . str_slug( $name ) . '.png' );
    }

    protected function save( $fileName, $data = '' )
    {
        file_put_contents( base_path( "storage/app/webdriver/" ) . $fileName, $data );
        return $this;
    }

    /**
     * @param $url
     * @param array $params
     * @return bool|mixed|\Psr\Http\Message\ResponseInterface
     */
    protected function get($url, Array $params = []) {
        try {
            $results = $this->driver->get(urldecode($url));
        } catch ( Exception $e ) {
            return false;
        }

        return $this->driver->getPageSource();
    }

    protected function checkProxy( $proxy )
    {
        $this->driver->get( 'http://www.whatismyip.org' );
        list( $ip, $port ) = explode( ":", $proxy );
        return $this->assert( $ip );
    }

    protected function solveCaptcha( $confirmString = '' )
    {

        \Log::debug( "-> Automating captcha \n");
        $siteKey = $this->driver->findElement( By::cssSelector( ".g-recaptcha" ) )->getAttribute( "data-sitekey" );
        \Log::debug( "   Site key is {$siteKey} \n");

        $curUrl = $this->driver->getCurrentURL();
        $solveUrl = "http://2captcha.com/in.php?key=6c2e1fddb8ebe61568c70d98f1264419&method=userrecaptcha&pageUrl=" . $curUrl . "&googlekey=" . $siteKey;
        $results = file_get_contents( $solveUrl );
        list( $result, $captchaId ) = explode( "|", $results );
        \Log::debug( "Captcha ID is {$captchaId} \n");
        sleep( 25 );
        $captchaCode = @json_decode( file_get_contents( "http://2captcha.com/res.php?key=6c2e1fddb8ebe61568c70d98f1264419&action=get&id=" . $captchaId . "&json=1" ), true );

        if ( $captchaCode['request'] == 'CAPCHA_NOT_READY' ) {
            sleep( 5 );
            \Log::debug( "   Refetching captcha 2nd try.. \n");
            $captchaCode = @json_decode( file_get_contents( "http://2captcha.com/res.php?key=6c2e1fddb8ebe61568c70d98f1264419&action=get&id=" . $captchaId . "&json=1" ), true );
        }

        if ( $captchaCode['request'] == 'CAPCHA_NOT_READY' ) {
            sleep( 30 );
            \Log::debug( "   Refetching captcha 3rd try.. \n");
            $captchaCode = @json_decode( file_get_contents( "http://2captcha.com/res.php?key=6c2e1fddb8ebe61568c70d98f1264419&action=get&id=" . $captchaId . "&json=1" ), true );
        }

        if ( $captchaCode['request'] == 'CAPCHA_NOT_READY' ) {
            sleep( 40 );
            \Log::debug( "   Refetching captcha 4th try.. \n");
            $captchaCode = @json_decode( file_get_contents( "http://2captcha.com/res.php?key=6c2e1fddb8ebe61568c70d98f1264419&action=get&id=" . $captchaId . "&json=1" ), true );
        }

        if ( $captchaCode['request'] == 'CAPCHA_NOT_READY' ) {
            sleep( 15 );
            \Log::debug( "   Refetching captcha 5th try.. \n");
            $captchaCode = @json_decode( file_get_contents( "http://2captcha.com/res.php?key=6c2e1fddb8ebe61568c70d98f1264419&action=get&id=" . $captchaId . "&json=1" ), true );
        }

        if ( strlen( $captchaCode['request'] ) > 50 ) {
            $this->driver->executeScript( 'document.getElementById(\'g-recaptcha-response\').value=\'' . $captchaCode['request'] . '\'' );
            \Log::debug( "   Submitted captcha code. \n");
            $this->driver->findElement( By::xpath( '//*[@id="submit_button"]' ) )->click();
            sleep( 10 );
            $source = $this->driver->getPageSource();

            if ( stristr( $source, $confirmString ) != false ) {
                \Log::debug( "   Captcha solved. \n");
                $this->snap("captchaSolved.png");
                return true;
            } else {
                \Log::debug( "   Error solving captcha. \n");
                $this->snap("captchaFailed.png");
                return false;
            }


        } else {
            \Log::debug( "-> Took more then 100 secs to complete captcha, aborting. \n ");
            return false;
        }

        return false;


    }

    protected function formatUrl( $url, Array $options )
    {
        foreach ($options as $key => $val) {
            $url = str_replace( "{" . $key . "}", $val, $url );
        }

        return $url;
    }
}