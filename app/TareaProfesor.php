<?php

namespace App;

use Illuminate\Database\Eloquent\Model;


class TareaProfesor extends Model
{
    protected $table = 'tarea_profesor';   
    
    protected $fillable = [
        'user_id', 'tarea_id', 'estado', 'inversion', 'tiempo',
    ];
}