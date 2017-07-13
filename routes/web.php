<?php

/*
|--------------------------------------------------------------------------
| user
|--------------------------------------------------------------------------
*/

Route::prefix('user')->group(function () {
	// public
	Route::get('login', 'UserController@getLogin')->name('login');
	Route::post('login', 'UserController@postLogin');

	Route::get('logout', 'UserController@getLogout');

	// protected
	Route::middleware('auth')->group(function () {
		Route::get('account', 'UserController@getAccount');

		Route::get('change-password', 'UserController@getChangePassword');
		Route::post('change-password', 'UserController@postChangePassword');

		Route::get('change-personal-info', 'UserController@getChangePersonalInfo');
		Route::post('change-personal-info', 'UserController@postChangePersonalInfo');
	});

});


/*
|--------------------------------------------------------------------------
| other
|--------------------------------------------------------------------------
*/

// public
Route::get('/', function()
{
    return redirect('home');
});

// protected
Route::middleware('auth')->group(function () {
	Route::get('/home', 'HomeController@index')->name('home');
	Route::get('test', 'TestController@getIndex');
	Route::get('/samples', 'SampleController@index')->name('samples');
	Route::get('/sequences', 'SequenceController@index')->name('sequences');
});

