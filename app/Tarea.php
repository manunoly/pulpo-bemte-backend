<?php

namespace App;

use Illuminate\Database\Eloquent\Model;


class Tarea extends Model
{
    protected $table = 'tareas';

    protected $fillable = [
        'user_id', 'materia', 'tema', 'fecha_entrega', 'hora_inicio', 'hora_fin', 'descripcion', 
        'formato_entrega', 'estado', 'activa', 'fecha_canc', 'archivo',
        'user_id_pro', 'tiempo_estimado', 'inversion', 
        'califacion_alumno', 'comentario_alumno', 'calificacion_profesor', 'comentario_profesor',
    ];
}