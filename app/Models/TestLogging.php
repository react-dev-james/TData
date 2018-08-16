<?php
/**
 * Created by PhpStorm.
 * User: sungwhikim
 * Date: 16/08/2018
 * Time: 21:36
 */

namespace App\Models;

class TestLogging
{

    public function test()
    {
        echo 'in test function';
        trigger_error("Custom Error 1", E_USER_WARNING);
        trigger_error("Custom Error 2", E_USER_WARNING);
    }
}