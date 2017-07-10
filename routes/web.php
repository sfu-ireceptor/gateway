<?php

/*
|--------------------------------------------------------------------------
| Public routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/home', 'HomeController@index')->name('home');

Route::get('test', 'TestController@getIndex');

Route::get('user/login', 'UserController@getLogin');
Route::post('user/login', 'UserController@postLogin');

Route::get('user/logout', 'UserController@getLogout');

Route::get('user/account', 'UserController@getAccount');

Route::get('user/change-password', 'UserController@getChangePassword');
Route::post('user/change-password', 'UserController@postChangePassword');

Route::get('user/change-personal-info', 'UserController@getChangePersonalInfo');
Route::post('user/change-personal-info', 'UserController@postChangePersonalInfo');

//Auth::routes();

