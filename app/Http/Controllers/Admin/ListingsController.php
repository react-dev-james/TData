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

        /* Load the saved report if provided */
        if ($request->has('reportId') && $request->get('reportType') == 'customListings') {
            $report = Report::find($request->reportId);
        } else {
            $report = null;
        }

        return view("admin.listings", [
            'savedReport' => $report
        ]);
    }

}
