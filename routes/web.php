<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Auth::routes();
Route::get( 'logout', function () {
    Auth::logout();
    return redirect( '/login' );
} );

Route::get('/', 'HomeController@index');


/* Admin routes */
Route::get( '/admin/listings', 'Admin\ListingsController@index' )->middleware( [ 'auth' ] );
Route::get( '/admin/users', 'Admin\UsersController@index' )->middleware( [ 'auth', 'admin' ] );
Route::get( "/admin/users/loginAs/{user}", 'Api\UsersController@loginAsUser' )->middleware( [ 'auth', 'admin' ] );
Route::get( '/admin/jobs', 'Admin\JobsController@index' )->middleware( [ 'auth', 'admin' ] );
Route::get('/admin/logs', '\Rap2hpoutre\LaravelLogViewer\LogViewerController@index')->middleware( [ 'auth', 'admin' ] );

/* Deployment */
Route::post('/bfC34RNEDYiC8Yc3C1c9LoB1Q9tHEEJE', 'DeploymentController@index');
