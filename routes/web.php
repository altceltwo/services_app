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
// Route::post('/login','AuthenticationController@login');
// Route::post('register','AuthenticationController@register');
Route::put('/updateClient', 'AuthenticationController@updateClient');
Route::get('getUser', 'AuthenticationController@getUser');

//Dispositos por usuario
Route::get('/devices', 'DeviceController@getDevice');
Route::post('/login','AuthenticationController@login');
Route::post('/addDevice', 'DeviceController@addDevice');


Route::post('/registro', 'AuthenticationController@register');


// STARTS POS ROUTES
Route::post('/login-pos','AuthenticationController@loginPos');
Route::post('/get-rates-pos','PosController@getRates');
Route::post('/get-rates-activation-pos','PosController@getRatesActivationPos');
Route::post('/save-recharge-pos','PosController@saveRechargePos');
Route::post('/save-data-activation-pos','PosController@saveDataActivationPos');
Route::post('/get-data-user-pos','PosController@getDataUser');
Route::post('/get-number-by-icc','PosController@getNumberByIcc');
// ENDS POS ROUTES

// RECHARGE CLIENT
Route::get('planes','RechargeClientController@getPlanes');

//UF
Route::get('/consultUf', 'DeviceController@consultUf');

// Consumos
Route::get('/consultCdrs', 'ConsumosController@consultCdrs');