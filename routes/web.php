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

Route::get('register', 'UserController@getRegister')->name('register');
Route::post('register', 'UserController@postRegister');

Route::get('user/forgot-password/{email?}', 'UserController@getForgotPassword');
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

// public list of repositories
Route::get('/repositories', 'HomeController@repositories')->name('repositories');

// public stats
Route::get('/samples/stats/{rest_service_id}/{repertoire_id}', 'SampleController@stats_sample_info')->name('samples-stats-info')->middleware('log_query');
Route::get('/samples/stats/{rest_service_id}/{repertoire_id}/{stat}', 'SampleController@stats')->name('samples-stats');

/*
|--------------------------------------------------------------------------
| Require authentication
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {
    // Test controller for testing functionality - should not be used in production.
    //Route::get('/test', 'TestController@getIndex');

    Route::get('/home', 'HomeController@index')->name('home');

    Route::get('/samples/json', 'SampleController@json')->name('samples-json')->middleware('log_query');
    Route::get('/samples/tsv', 'SampleController@tsv')->name('samples-tsv')->middleware('log_query');
    Route::post('/samples/{type?}', 'SampleController@postIndex')->name('samples-post');
    Route::get('/samples/{type?}', 'SampleController@index')->name('samples')->middleware('log_query');
    Route::get('/samples/field/{id}', 'SampleController@field')->name('samples-field');
    Route::get('/samples/field-data/{id}', 'SampleController@field_data')->name('samples-field-data');
    Route::get('/samples/count-stats-popup-open', 'SampleController@countStatsPopupOpen')->name('samples-count-stats-popup-open');

    Route::post('/sequences', 'SequenceController@postIndex')->name('sequences-post');
    Route::get('/sequences', 'SequenceController@index')->name('sequences')->middleware('log_query');
    Route::get('/sequences-quick-search', 'SequenceController@quickSearch')->name('sequences-quick-search')->middleware('log_query');
    Route::post('/sequences-quick-search', 'SequenceController@postQuickSearch')->name('sequences-quick-search-post');
    Route::get('/sequences-download', 'SequenceController@download')->name('sequences-download');

    Route::post('/clones', 'CloneController@postIndex')->name('clones-post');
    Route::get('/clones', 'CloneController@index')->name('clones')->middleware('log_query');
    Route::get('/clones-download', 'CloneController@download')->name('clones-download');

    Route::post('/cells', 'CellController@postIndex')->name('cells-post');
    Route::get('/cells', 'CellController@index')->name('cells')->middleware('log_query');
    Route::get('/cells-download', 'CellController@download')->name('cells-download');

    Route::prefix('user')->group(function () {
        Route::get('account', 'UserController@getAccount');

        Route::get('change-password', 'UserController@getChangePassword');
        Route::post('change-password', 'UserController@postChangePassword');

        Route::get('change-personal-info', 'UserController@getChangePersonalInfo');
        Route::post('change-personal-info', 'UserController@postChangePersonalInfo');

        Route::get('welcome', 'UserController@getWelcome');
    });

    Route::prefix('bookmarks')->group(function () {
        Route::get('', 'BookmarkController@getIndex');
        Route::post('add', 'BookmarkController@postAdd');
        Route::post('delete', 'BookmarkController@postDelete');
        Route::get('delete/{id}', 'BookmarkController@getDelete');
    });

    Route::prefix('downloads')->group(function () {
        Route::get('', 'DownloadController@getIndex');
        Route::get('download/{id}', 'DownloadController@getDownload');
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
        Route::get('download-analysis/{id}', 'JobController@getDownloadAnalysis');
        Route::get('download-output-log/{id}', 'JobController@getDownloadOutput');
        Route::get('download-error-log/{id}', 'JobController@getDownloadError');
        Route::get('view/file/{id}', 'JobController@getViewJobFile');
        Route::get('view/show', 'JobController@getShow')->name('job.show');
        Route::get('view/{id}', 'JobController@getView');
        Route::get('job-history/{id}', 'JobController@getJobHistory');
        Route::get('status/{id}', 'JobController@getStatus');
        Route::get('delete/{id}', 'JobController@getDelete');
        Route::get('cancel/{id}', 'JobController@getCancel');
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
        Route::get('edit-user/{id}', 'AdminController@getEditUser');
        Route::post('edit-user', 'AdminController@postEditUser');
        Route::get('samples/update-cache', 'AdminController@getUpdateSampleCache');
        Route::get('samples/update-sequence_count/{rest_service_id}', 'AdminController@getUpdateSequenceCount');
        Route::get('samples/update-clone_count/{rest_service_id}', 'AdminController@getUpdateCloneCount');
        Route::get('samples/update-cell_count/{rest_service_id}', 'AdminController@getUpdateCellCount');
        Route::get('samples/update-epitopes/{rest_service_id}', 'AdminController@getUpdateEpitopes');
        Route::get('field-names/{api_version?}', 'AdminController@getFieldNames');
        Route::get('queries', 'AdminController@queries');
        Route::get('downloads', 'AdminController@downloads');
        Route::get('jobs', 'AdminController@jobs');
        Route::get('downloads/multiple-ipas', 'AdminController@downloadsMultipleIPAs');
        Route::get('queries2', 'AdminController@queries2');
        Route::get('queries/months/{n}', 'AdminController@queriesMonths');
        Route::get('queries2/months/{n}', 'AdminController@queriesMonths2');
        Route::get('queries/{id}', 'AdminController@query');
    });

    // other
    Route::get('/ireceptor-survey', 'HomeController@survey')->name('survey');
    Route::get('/ireceptor-survey-go', 'HomeController@surveyGo')->name('survey-go');
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
// this is no longer necessary, can be removed.
// This also is the query that is causing the mysql lock/commit problem
// See IR-3079.
/*
if (! App::runningInConsole()) {
    App\Stats::incrementNbRequests();
}
*/
