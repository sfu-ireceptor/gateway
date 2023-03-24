<?php

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| They accept POST requests without CSRF token.
|
*/

// Agave jobs notifications
Route::any('job/update-status/{id}/{status}', 'UtilController@updateJobStatus');

// Deployment trigger for GitHub hook
Route::any('deploy', 'UtilController@deploy');
