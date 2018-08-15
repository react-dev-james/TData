<?php
/**
 * Created by PhpStorm.
 * User: sungwhikim
 * Date: 23/07/2018
 * Time: 14:52
 */

namespace App\Http\Controllers;


Use Illuminate\Support\Facades\Log;

class DeploymentController extends Controller
{
    public function index()
    {
        /* code from https://gist.github.com/nichtich/5290675#file-deploy-php and modified for our needs */
        // Actually run the update
        $commands = array(
            'echo $PWD',
            'whoami',
            //'git pull',
            'git fetch --all',
            'git reset --hard origin/master',
            'git status',
            'composer update',
            //'git submodule sync',
            //'git submodule update',
            //'git submodule status',
            //'test -e /usr/share/update-notifier/notify-reboot-required && echo "system restart required"',
        );

        $output = '';
        $log = '';
        foreach($commands AS $command){
            // Run it
            $tmp = shell_exec("$command 2>&1");
            // Output
            $output .= "<span style=\"color: #6BE234;\">\$</span> <span style=\"color: #729FCF;\">{$command}\n</span>";
            $output .= htmlentities(trim($tmp)) . "\n";
            $log  .= "\$ $command\n".trim($tmp)."\n";
        }
        $log .= "\n";
        Log::info($log);
        echo $output;

    }
}