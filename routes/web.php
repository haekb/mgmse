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
// Brought this back! Just shows the project's github.
Route::get('/', 'WebController@index');
Route::get('/privacy', 'WebController@privacy');
Route::get('/{game_id}/motd', 'WebController@motd');
Route::get('/{game_id}/version', 'WebController@version');
