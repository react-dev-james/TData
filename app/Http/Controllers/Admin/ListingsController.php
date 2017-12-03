<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Listing;
use App\Report;

class ListingsController extends Controller
{
    public function __construct()
    {
        $this->middleware("auth");
    }

    public function index( Request $request )
    {
        /* Run ticket network import of not running already */
        exec( "ps aux | grep -i 'tickets:importtn' | grep -v grep", $pids );
        if (empty($pids)) {
            exec( "php " . base_path( "artisan" ) . " tickets:importtn > /dev/null 2>&1 &" );
        }


        return view("admin.listings", [
        ]);
    }

}
