<?php

namespace App;

use Illuminate\Database\Eloquent\Model;


class TareaEjercicio extends Model
{
    protected $table = 'tarea_ejercicio';   

    protected $fillable = [
        'user_id', 'tarea_id', 'archivo', 'drive',
    ];
}