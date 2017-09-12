@extends('layouts.app')

@section('body')
    <body class="bg-image-6">
        @endsection

        @section('nav')
            <bomb-nav current-page="login"></bomb-nav>
        @endsection

        @section('content')

            <md-whiteframe md-elevation="3" class="margin-top-55 text-center padding-50 col-lg-4 col-lg-offset-4 col-sm-12 bg-white-transparent">
                <div>
                    <img src="/img/logo_xs.png" width="80" height="80"/>
                    <h4 class="font-weight-300">Reset Your Password</h4>
                    <bomb-password-reset-confirm redirect-url="/"></bomb-password-reset-confirm>
                </div>
                <div class="clearfix"></div>
            </md-whiteframe>



@endsection
