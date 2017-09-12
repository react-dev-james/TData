<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\User;

class JobsController extends Controller
{
    public function __construct()
    {
        $this->middleware("auth");
    }

    public function index( Request $request )
    {
        return view("admin.jobs");
    }

}
