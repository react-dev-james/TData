<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\User;

class UsersController extends Controller
{
    public function __construct()
    {
        $this->middleware("auth");
    }

    public function index( Request $request )
    {
        return view("admin.users");
    }

    public function loginAsUser( Request $request, User $user )
    {

        \Auth::loginUsingId( $user->id );

        $request->session()->flash( "message", [
            'type'    => 'success',
            'icon'    => null,
            'title'   => 'Logged In',
            'message' => "You are now logged in as " . $user->name
        ] );

        return redirect( "/dashboard" );

    }
}
