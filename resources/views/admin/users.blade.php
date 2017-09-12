@extends('layouts.app')

@section('body')
    <body class="bg-grey-100">
@endsection

@section('header-scripts')
            <script type="text/javascript" src="/js/plugins/amcharts/amcharts.js"></script>
            <script type="text/javascript" src="/js/plugins/amcharts/serial.js"></script>
            <script type="text/javascript" src="/js/plugins/amcharts/amstock.js"></script>
            <script type="text/javascript" src="/js/plugins/amcharts/themes/dark.js"></script>
@endsection

@section('nav')
    <bomb-nav current-page="admin"></bomb-nav>
@endsection

@section('content')
    <div style=" padding: 20px; height: auto; margin-right: 0px;">

        <div class="col-lg-12">
            <trans-admin-users></trans-admin-users>

        </div>
        <div class="clearfix"></div>

    </div>

@endsection

@section('scripts')


@endsection