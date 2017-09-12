<?php

use Illuminate\Http\Request;

Route::post( 'login', 'Api\AuthController@login' );
Route::post( 'register', 'Auth\RegisterController@register' );

/* Files */
Route::post( '/files/create', 'Api\FilesController@create' );

/* Reports */
Route::get( "/reports", 'Api\ReportsController@getReports' );
Route::post( "/reports/save", 'Api\ReportsController@saveReport' );
Route::post( '/reports/delete/{report}', 'Api\ReportsController@delete' );
Route::get( "/reports/{reportType}", 'Api\ReportsController@report' );

/* Listings */
Route::get( "/listings", 'Api\ListingsController@index' );
Route::get( '/listings/{listing}', 'Api\ListingsController@fetch' );
Route::post( '/listings/update/{listing}', 'Api\ListingsController@update' );
Route::post( '/listings/updateOutlierStatus/{listing}', 'Api\ListingsController@updateOutlierStatus' );
Route::post( '/listings/create', 'Api\ListingsController@create' );
Route::post( '/listings/delete/{listing}', 'Api\ListingsController@delete' );

/* Locations */
Route::get( "/locations", 'Api\LocationsController@index' );
Route::get( '/locations/{location}', 'Api\LocationsController@fetch' );
Route::post( '/locations/update/{location}', 'Api\LocationsController@update' );
Route::post( '/locations/create', 'Api\LocationsController@create' );
Route::post( '/locations/region/create', 'Api\LocationsController@createRegion' );
Route::post( '/locations/region/listings', 'Api\LocationsController@getRegionListings' );
Route::post( '/locations/delete/{location}', 'Api\LocationsController@delete' );

/* Users */
Route::get( "/users", 'Api\UsersController@index' );
Route::get( '/users/{user}', 'Api\UsersController@fetch' );
Route::post( '/users/update/{user}', 'Api\UsersController@update' );
Route::post( '/users/create', 'Api\UsersController@create' );
Route::post( '/users/delete/{user}', 'Api\UsersController@delete' );

/* Jobs */
Route::get( "/jobs", 'Api\JobsController@index' );
Route::post( "/jobs/queue", 'Api\JobsController@queueJob' );
Route::post( "/jobs/requeue", 'Api\JobsController@requeueJob' );
Route::post( "/jobs/addToQueue", 'Api\JobsController@addToQueue' );