<?php

namespace App;

use Illuminate\Database\Eloquent\Model;


class Combo extends Model
{
    protected $table = 'combos';
    protected $primaryKey = 'nombre'; 
    public $incrementing = false;

    public static $rules = [
        'nombre' => 'required|unique:combos',
        'beneficios' => 'required',
    ];
    public static $messages = [
        'nombre.required' => 'Indique el nombre del Combo.',
        'nombre.unique' => 'Combo ya ingresado.',
        'beneficios.required' => 'Indique los Beneficios del Combo.',
    ];
}