@extends('layouts.app')

@section('body')
    <body class="bg-grey-100">
@endsection

@section('header-scripts')
    <script type="text/javascript" src="/js/plugins/amcharts/amcharts.js"></script>
    <script type="text/javascript" src="/js/plugins/amcharts/serial.js"></script>
    <script type="text/javascript" src="/js/plugins/amcharts/amstock.js"></script>
    <script type="text/javascript" src="/js/plugins/amcharts/themes/dark.js"></script>
    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/clipboard.js/1.7.1/clipboard.min.js"></script>
    <script type="text/javascript">
        window.appShared.activeReport = <?php echo @json_encode($savedReport); ?>;
    </script>
@endsection



@section('nav')
    <bomb-nav current-page="listings"></bomb-nav>
@endsection

@section('content')
    <div style=" padding-top: 20px; padding-bottom: 20px; height: auto; margin-right: 0px;">

        <div class="col-lg-12 no-padding">
            <trans-admin-listings ></trans-admin-listings>

        </div>
        <div class="clearfix"></div>

    </div>

@endsection

@section('scripts')


@endsection