<!DOCTYPE html>
@section('html')
    <html lang="en">
        @show
        <head>
            <meta charset="utf-8">
            <meta http-equiv="X-UA-Compatible" content="IE=edge">
            <meta name="viewport" content="width=device-width, initial-scale=1">

        @section ('meta')
            <!-- META -->
                <meta itemprop="name" content="{{ config('app.name') }}">
                <meta itemprop="url" content="{{ config('app.url') }}"/>
                <meta name="description" itemprop="description" content="{{ config('app.name') }}">
                <meta name="author" content="SageGroupy">

                <meta property="og:url" content="{{ config('app.url') }}"/>
                <meta property="og:type" content="website"/>
                <meta property="og:title" content="{{ config('app.name') }}"/>
                <meta property="og:description" content="{{ config('app.name') }}"/>
                <meta property="twitter:url" content="{{ config('app.url') }}"/>
                <meta property="twitter:type" content="website"/>
                <meta property="twitter:title" content="{{ config('app.name') }}"/>
                <meta property="twitter:description" content="{{ config('app.name') }}"/>

                <meta property="og:image" content="{{ env("APP_URL")}}/img/icon_xxs.png"/>
                <meta property="twitter:image" content="{{ env("APP_URL")}}/img/icon_xxs.png"/>
            @show

            @section ('title')
                <title>{{ config('app.name') }}</title>
            @show

        <!-- Fonts -->
            <link href="https://fonts.googleapis.com/css?family=Raleway:100,600" rel="stylesheet" type="text/css">
            <link href='https://fonts.googleapis.com/css?family=Roboto:300,400,500,700|Material+Icons' rel="stylesheet" type="text/css">

            <!-- Styles -->
            <link href="/css/vendor.css" rel="stylesheet" type="text/css">
            <link href="/css/app.css" rel="stylesheet" type="text/css">

            <!-- Styles -->
            @section('header-styles')
            @show

        <!-- Scripts -->
            <script>
                /* Data objects for the app */
                window.appShared = {
                    user: {},
                    notifications: [],
                    listings: [],
                    listing: {
                        updates: [],
                        stats: {}
                    },
                    page: '',
                    state: {},
                    meta: {},
                    baseUrl: '{{ url('/') }}'
                };
                window.appShared.user = <?php echo json_encode( \Auth::check() ? Auth::user() : [] ); ?>;
                window.Laravel = <?php echo json_encode( [
                'csrfToken'    => csrf_token(),
                '_token'       => csrf_token()
                    ] ); ?>
            </script>
            @section('header-scripts')
            @show

        </head>
        @section('body')
            <body class="bg-grey-800">
        @show
                <div id="app">
                    @section('nav')
                    @show

                    <div class="col-lg-12 content">
                        <md-snackbar md-position="top right" ref="snackbar" md-duration="5000" v-cloak>
                            <span>@{{snackBarMessage}}</span>
                            <md-button class="md-accent" @click.native="$refs.snackbar.close()">Close</md-button>
                        </md-snackbar>
                        @section('content')
                        @show

                        @section('footer')
                        @show
                    </div>

                    <div class="clearfix"></div>
                </div>
            </body>
            <script src="/js/app.js"></script>


        @section('scripts')
        @show
    </html>
