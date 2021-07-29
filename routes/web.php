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

Route::get('/admin/reportes', function () {
    return view('vendor.voyager.reportes.report');
});

Route::get('/admin/marketing', 'Voyager\MarketingController@load');

Route::group(['prefix' => 'admin'], function () {

    Route::get('profesorPagar', 'Voyager\ProfesoresController@pagar')->name('profesor.pagar');
    Route::get('profesorMultar', 'Voyager\ProfesoresController@multar')->name('profesor.multar');
    Route::post('updateOrCreate', 'Voyager\ProfesoresController@updateOrCreate')->name('profesor.updateOrCreate');
    
    Route::post('refund', 'Voyager\PaymentezController@refund')->name('paymentez.refund');
    
    Route::get('buscar', 'Voyager\ReportesController@multar')->name('reportes.buscar');
    Route::get('masivo', 'Voyager\MarketingController@enviar')->name('marketing.enviar');

    Voyager::routes();
});
