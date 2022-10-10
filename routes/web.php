<?php

use Illuminate\Support\Facades\Route;

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

Route::get('/', function () {
    return view('welcome');
});

//Autenticación
Route::post('/login','AuthenticationController@login');
Route::post('register','AuthenticationController@register');
Route::post('updateUser', 'AuthenticationController@updateUser');
Route::get('getUser', 'AuthenticationController@getUser');

//Dispositos por usuario
Route::get('devices', 'DeviceController@getDevice');
Route::post('/login','AuthenticationController@login');

Route::post('/registro', 'AuthenticationController@register');
