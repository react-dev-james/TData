@extends('layouts.app')

@section('body')
    <body class="bg-grey-100">
@endsection

@section('nav')
    <bomb-nav current-page="home"></bomb-nav>
@endsection

@section('sidenav')
@endsection

@section('content')

            <md-whiteframe class="margin-top-55 bg-white-transparent text-center padding-50 col-lg-8 col-lg-offset-2 col-sm-12">
                <h1>Welcome to {{ config('app.name') }}</h1>

                <md-input-container v-bind:class="{ 'md-input-invalid' : errors.email }">
                    <label>Email</label>
                    <md-input type="text" v-model="shared.meta.email"></md-input>
                    <span v-if="errors.email" class="md-error">@{{ errors.password[0] }}</span>
                </md-input-container>

                <md-button  class="md-raised md-primary">Sign Up</md-button>
            </md-whiteframe>

@endsection
