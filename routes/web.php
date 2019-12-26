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

Route::get('/', function () {
    return view('welcome');
});

Route::group(['prefix' => 'admin'], function () {

    Route::get('profesorPagar', 'Voyager\ProfesoresController@pagar')->name('profesor.pagar');
    Route::get('profesorMultar', 'Voyager\ProfesoresController@multar')->name('profesor.multar');

    Voyager::routes();
});
