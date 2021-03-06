<?php

/*
|--------------------------------------------------------------------------
| Public
|--------------------------------------------------------------------------
*/

// home
Route::get('/', function () {
    return redirect('home');
});

// user authentication
Route::get('login', 'UserController@getLogin')->name('login');
Route::post('login', 'UserController@postLogin');

Route::get('logout', 'UserController@getLogout');

Route::get('user/forgot-password', 'UserController@getForgotPassword');
Route::post('user/forgot-password', 'UserController@postForgotPassword');
Route::get('user/forgot-password-email-sent', 'UserController@getForgotPasswordEmailSent');

Route::get('user/reset-password/{token}', 'UserController@getResetPassword');
Route::get('user/reset-password-confirmation', 'UserController@getResetPasswordConfirmation');

// about
Route::get('about', 'HomeController@about')->name('about');

// news
Route::get('news', 'HomeController@news')->name('news');

// fields definitions
Route::get('/fields-definitions', 'HomeController@fieldsDefinitions')->name('fields-definitions');

// public stats
Route::get('/samples/stats/{rest_service_id}/{repertoire_id}', 'SampleController@stats_sample_info')->name('samples-stats-info')->middleware('log_query');
Route::get('/samples/stats/{rest_service_id}/{repertoire_id}/{stat}', 'SampleController@stats')->name('samples-stats');

// CANARIE monitoring - dynamic pages
Route::get('platform/info', 'CanarieController@platformInfo');
Route::get('auth/service/info', 'CanarieController@authInfo');
Route::get('computation/service/info', 'CanarieController@computationInfo');

Route::get('platform/stats', 'CanarieController@platformStats');
Route::get('auth/service/stats', 'CanarieController@authStats');
Route::get('computation/service/stats', 'CanarieController@computationStats');

// CANARIE monitoring - static pages
Route::get('canarie', 'CanarieController@links');

Route::get('platform/{page}', 'CanarieController@linkPage');
Route::get('auth/service/{page}', 'CanarieController@linkPage');
Route::get('computation/service/{page}', 'CanarieController@linkPage');

// just for dev
Route::get('test', 'TestController@getIndex')->name('test-page');
Route::any('test2', 'TestController@index2');
Route::any('phpinfo', 'TestController@phpinfo');
Route::any('wait/{seconds}', 'TestController@wait');
Route::get('email', 'TestController@email');

/*
|--------------------------------------------------------------------------
| Require authentication
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {
    Route::get('/home', 'HomeController@index')->name('home');
    Route::post('/samples', 'SampleController@postIndex')->name('samples-post');
    Route::get('/samples', 'SampleController@index')->name('samples')->middleware('log_query');
    Route::get('/samples/field/{id}', 'SampleController@field')->name('samples-field');
    Route::get('/samples/field-data/{id}', 'SampleController@field_data')->name('samples-field-data');
    Route::get('/samples/json', 'SampleController@json')->name('samples-json')->middleware('log_query');
    Route::get('/samples/tsv', 'SampleController@tsv')->name('samples-tsv')->middleware('log_query');
    Route::get('/samples/count-stats-popup-open', 'SampleController@countStatsPopupOpen')->name('samples-count-stats-popup-open');
    Route::post('/sequences', 'SequenceController@postIndex')->name('sequences-post');
    Route::get('/sequences', 'SequenceController@index')->name('sequences')->middleware('log_query');
    Route::get('/sequences-quick-search', 'SequenceController@quickSearch')->name('sequences-quick-search')->middleware('log_query');
    Route::post('/sequences-quick-search', 'SequenceController@postQuickSearch')->name('sequences-quick-search-post');
    Route::get('/sequences-download', 'SequenceController@download')->name('sequences-download');

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

    Route::prefix('downloads')->group(function () {
        Route::get('', 'DownloadController@getIndex');
        Route::get('cancel/{id}', 'DownloadController@getCancel');
        Route::get('delete/{id}', 'DownloadController@getDelete');
        Route::get('undo-delete/{id}', 'DownloadController@getUndoDelete');
        Route::get('bookmark/{id}', 'DownloadController@getBookmark');
    });

    Route::prefix('systems')->group(function () {
        Route::get('', 'SystemController@getIndex');
        Route::post('add', 'SystemController@postAdd');
        Route::post('select', 'SystemController@postSelect');
        Route::get('delete/{id}', 'SystemController@getDelete');
    });

    Route::prefix('jobs')->group(function () {
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
        Route::get('database-stats/{id}', 'AdminController@getDatabaseStats');
        Route::get('update-database/{id}/{enabled}', 'AdminController@getUpdateDatabase');
        Route::get('update-chunk-size/{id}', 'AdminController@getUpdateChunkSize');
        Route::get('news', 'AdminController@getNews');
        Route::get('add-news', 'AdminController@getAddNews');
        Route::post('add-news', 'AdminController@postAddNews');
        Route::get('edit-news/{id}', 'AdminController@getEditNews');
        Route::post('edit-news', 'AdminController@postEditNews');
        Route::get('delete-news/{id}', 'AdminController@getDeleteNews');
        Route::get('users/{sort?}', 'AdminController@getUsers');
        Route::get('users2/{sort?}', 'AdminController@getUsers2');
        Route::get('add-user', 'AdminController@getAddUser');
        Route::post('add-user', 'AdminController@postAddUser');
        Route::get('edit-user/{username}', 'AdminController@getEditUser');
        Route::post('edit-user', 'AdminController@postEditUser');
        Route::get('delete-user/{username}', 'AdminController@getDeleteUser');
        Route::get('samples/update-cache', 'AdminController@getUpdateSampleCache');
        Route::get('samples/update-sequence_count/{rest_service_id}', 'AdminController@getUpdateSequenceCount');
        Route::get('field-names', 'AdminController@getFieldNames');
        Route::get('queries', 'AdminController@queries');
        Route::get('downloads', 'AdminController@downloads');
        Route::get('queries2', 'AdminController@queries2');
        Route::get('queries/months/{n}', 'AdminController@queriesMonths');
        Route::get('queries2/months/{n}', 'AdminController@queriesMonths2');
        Route::get('queries/{id}', 'AdminController@query');
    });
});

/*
|--------------------------------------------------------------------------
| Misc
|--------------------------------------------------------------------------
*/

// legacy route redirect
Route::get('user/login', function () {
    return redirect('login');
});

// update page count for CANARIE
if (! App::runningInConsole()) {
    App\Stats::incrementNbRequests();
}
