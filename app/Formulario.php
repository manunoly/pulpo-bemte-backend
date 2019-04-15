<?php

namespace App;

use Illuminate\Database\Eloquent\Model;


class Formulario extends Model
{
    protected $table = 'formulario';

    protected $fillable = [
        'user_id', 'cedula', 'clases', 'proyectos', 'disponible', 'hoja_vida', 'titulo', 'estado',
    ];
}