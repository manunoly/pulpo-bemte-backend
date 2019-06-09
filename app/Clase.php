<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Clase extends Model
{
    protected $table = 'clases';

    protected $fillable = [
        'user_id', 'materia', 'tema', 'personas', 'duracion', 'hora1', 'hora2', 
        'combo', 'ubicacion', 'seleccion_profesor', 'fecha', 'hora_prof', 'fecha_canc',
        'user_id_pro', 'estado', 'calle', 'referencia', 'quien_preguntar', 'activa', 
        'califacion_alumno', 'comentario_alumno', 'calificacion_profesor', 'comentario_profesor',
    ];
}