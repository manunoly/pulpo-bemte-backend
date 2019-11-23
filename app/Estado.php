<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Estado extends Model
{
    protected $table = 'estados';
    protected $primaryKey = 'codigo'; 
    public $incrementing = false;
    
    protected $fillable = [
        'codigo', 'alumnoClase', 'profesorClase', 'alumnoTarea', 'profesorTarea',
    ];
}