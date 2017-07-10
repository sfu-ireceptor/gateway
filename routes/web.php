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

//Auth::routes();

