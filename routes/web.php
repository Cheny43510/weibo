<?php

Route::get('/',[App\Http\Controllers\StaticPagesController::class,'home']);

// Route::get('/', 'StaticPagesController@home');
Route::get('/help', 'StaticPagesController@help');
Route::get('/about', 'StaticPagesController@about');


