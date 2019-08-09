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
Route::get('horas-alumno', 'CombosController@horasAlumno');
Route::get('horas-totales', 'CombosController@horasTotales');


Route::post('actualizar-tareas', 'ProfesorController@actualizarTareas');
Route::post('actualizar-clases', 'ProfesorController@actualizarClases');
Route::post('actualizar-disponible', 'ProfesorController@actualizarDisponible');
Route::post('calificar-profesor', 'ProfesorController@calificarProfesor');
Route::post('aplicar-tarea', 'ProfesorController@aplicarTarea');
Route::post('aplicar-clase', 'ProfesorController@aplicarClase');


Route::post('solicitar-tarea', 'TareasController@solicitarTarea');
Route::get('tarea-activa', 'TareasController@tareaActiva');
Route::get('tareas-disponibles', 'TareasController@tareasDisponibles');
Route::post('tarea-terminar', 'TareasController@tareaTerminar');
Route::get('lista-tareas', 'TareasController@listaTareas');


Route::post('calificar-alumno', 'AlumnosController@calificarAlumno');
Route::post('pagar-con-combo', 'AlumnosController@pagarConCombo');
Route::post('aplicar-profesor', 'AlumnosController@aplicarProfesor');


Route::post('solicitar-clase', 'ClasesController@solicitarClase');
Route::get('clase-activa', 'ClasesController@claseActiva');
Route::get('clases-disponibles', 'ClasesController@clasesDisponibles');
Route::post('clase-terminar', 'ClasesController@claseTerminar');
Route::get('lista-clases', 'ClasesController@listaClases');
Route::post('clase-confirmar', 'ClasesController@claseConfirmar');


Route::post('subir-archivo', 'FicherosController@subirArchivo');
Route::post('subir-ejercicio', 'FicherosController@subirEjercicio');
Route::post('subir-transferencia', 'FicherosController@subirTransferencia');