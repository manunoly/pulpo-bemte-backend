<?php

namespace App;

use Illuminate\Database\Eloquent\Model;


class Estado extends Model
{
    protected $table = 'estados';
    protected $primaryKey = 'estado'; 
    public $incrementing = false;
    
    protected $fillable = [
        'estado', 'alumnoClase', 'profesorClase', 'alumnoTarea', 'profesorTarea',
    ];
}
