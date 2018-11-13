<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Listing;
use App\Report;

class ListingsTicketMasterController extends Controller
{
    public function __construct()
    {
        $this->middleware("auth");
    }

    public function index( Request $request )
    {
        return view("admin.listings-ticket-master", []);
    }
}
