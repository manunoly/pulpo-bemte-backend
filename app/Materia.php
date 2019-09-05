<?php

namespace App;

use Illuminate\Database\Eloquent\Model;


class Materia extends Model
{
    protected $table = 'materias';
    protected $primaryKey = 'nombre'; 
    public $incrementing = false;

    public static $rules = [
        'nombre' => 'unique:materias',
    ];
    public static $messages = [
        'nombre.unique' => 'Materia ya ingresada.'
    ];
}
