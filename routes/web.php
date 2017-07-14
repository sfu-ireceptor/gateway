<?php

/*
|--------------------------------------------------------------------------
| Public
|--------------------------------------------------------------------------
*/

Route::get('/', function() {
    return redirect('home');
});

Route::get('user/login', 'UserController@getLogin')->name('login');
Route::post('user/login', 'UserController@postLogin');

Route::get('user/logout', 'UserController@getLogout');


/*
|--------------------------------------------------------------------------
| Require authentication
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {

	Route::get('/home', 'HomeController@index')->name('home');
	Route::get('/samples', 'SampleController@index')->name('samples');
	Route::get('/sequences', 'SequenceController@index')->name('sequences');

	Route::prefix('user')->group(function () {
		Route::get('account', 'UserController@getAccount');

		Route::get('change-password', 'UserController@getChangePassword');
		Route::post('change-password', 'UserController@postChangePassword');

		Route::get('change-personal-info', 'UserController@getChangePersonalInfo');
		Route::post('change-personal-info', 'UserController@postChangePersonalInfo');
	});

	Route::prefix('bookmarks')->group(function () {
	     Route::get('', 'BookmarkController@getIndex');
	     Route::post('add', 'BookmarkController@postAdd');
	     Route::post('delete', 'BookmarkController@postDelete');
	     Route::get('delete/{id}', 'BookmarkController@getDelete');
	});

	Route::prefix('systems')->group(function () {
	    Route::get('', 'SystemController@getIndex');
	    Route::post('add', 'SystemController@postAdd');
	    Route::post('select', 'SystemController@postSelect');
	    Route::get('delete/{id}', 'SystemController@getDelete');
	});

	Route::prefix('job')->group(function () {
	    Route::get('', 'JobController@getIndex');
	    Route::get('job-data/{job_id}', 'JobController@getJobData');
	    Route::get('job-list-grouped-by-month', 'JobController@getJobListGroupedByMonth');
	    Route::post('launch-app', 'JobController@postLaunchApp');
	    Route::get('view/{id}', 'JobController@getView');
	    Route::get('agave-history/{id}', 'JobController@getAgaveHistory');
	    Route::get('status/{id}', 'JobController@getStatus');
	    Route::get('delete/{id}', 'JobController@getDelete');
	});

	Route::prefix('admin')->group(function () {
		Route::get('queues', 'AdminController@getQueues');
		Route::get('databases', 'AdminController@getDatabases');
		Route::post('update-database', 'AdminController@postUpdateDatabase');
		Route::get('users', 'AdminController@getUsers');
		Route::get('add-user', 'AdminController@getAddUser');
		Route::post('add-user', 'AdminController@postAddUser');
		Route::get('edit-user/{username}', 'AdminController@getEditUser');
		Route::post('edit-user', 'AdminController@postEditUser');
		Route::get('delete-user/{username}', 'AdminController@getDeleteUser');
	});

	Route::get('test', 'TestController@getIndex');

});


