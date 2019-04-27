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


Route::post('login', 'RegistroController@login');
Route::post('registro', 'RegistroController@registro');
Route::post('actualizar-cuenta', 'RegistroController@actualizarCuenta');
Route::post('actualizar-celular', 'RegistroController@actualizarCelular');

Route::get('eliminar-cuenta', 'RegistroController@eliminarCuenta');
Route::post('reset', 'RegistroController@resetPassword');
Route::get('reset/{confirmation_code}/{email}', 'RegistroController@validar'); 
Route::post('reset_pw', 'RegistroController@actualizarPW');


Route::get('lista-ciudades', 'CiudadController@listaCiudades');
Route::get('lista-sedes', 'CiudadController@listaSedes');

Route::get('lista-materias', 'MateriasController@listaMaterias');

Route::get('lista-combos', 'CombosController@listaCombos');
Route::get('combo-horas', 'CombosController@listaCombosHoras');
Route::post('combo-compra', 'CombosController@compraComboAlumno');
Route::get('combo-alumno', 'CombosController@horasComboAlumno');


Route::post('actualizar-tareas', 'ProfesorController@actualizarTareas');
Route::post('actualizar-clases', 'ProfesorController@actualizarClases');
Route::post('actualizar-disponible', 'ProfesorController@actualizarDisponible');
Route::post('calificar-profesor', 'ProfesorController@calificarProfesor');


Route::post('solicitar-tarea', 'TareasController@solicitarTarea');
Route::get('tarea-activa', 'TareasController@tareaActiva');


Route::post('calificar-alumno', 'AlumnosController@calificarAlumno');