<?php
/**
 * Created by PhpStorm.
 * User: sungwhikim
 * Date: 16/08/2018
 * Time: 21:36
 */

namespace App\Models;

use Mockery\Exception;

class TestLogging
{

    public function test($logable)
    {
        echo 'in test function';
        $logable->logInfo('in test function');
    }
}