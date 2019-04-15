<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});


Route::post('registro', 'RegistroController@registro');
Route::post('actualizar-cuenta', 'RegistroController@actualizarCuenta');
Route::get('eliminar-cuenta', 'RegistroController@eliminarCuenta');
Route::post('login', 'RegistroController@login');
Route::post('reset', 'RegistroController@resetPassword');
Route::get('reset/{confirmation_code}/{email}', 'RegistroController@validar'); 
Route::post('reset_pw', 'RegistroController@actualizarPW');