<?php

namespace App;

use Illuminate\Database\Eloquent\Model;


class Alumno extends Model
{
    protected $table = 'alumnos';
    protected $primaryKey = 'user_id'; 
    public $incrementing = false;

    protected $fillable = [
        'user_id', 'celular', 'correo', 'nombres', 'apellidos', 'correo', 'apodo', 'ubicacion', 'ciudad', 
        'ser_profesor', 'activo', 'billetera',
    ];
}