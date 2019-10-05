<?php

namespace App;

use Illuminate\Database\Eloquent\Model;


class Profesore extends Model
{
    protected $table = 'profesores';
    protected $primaryKey = 'user_id'; 
    public $incrementing = false;
    
    protected $fillable = [
        'user_id', 'celular', 'correo', 'nombres', 'apellidos', 'cedula', 'correo', 'apodo', 'ubicacion', 
        'ciudad', 'clases', 'tareas', 'disponible', 'hoja_vida', 'titulo', 'activo', 
        'cuenta', 'banco', 'tipo_cuenta', 'valor_clase', 'valor_tarea',
        'descripcion', 'fecha_nacimiento', 'genero',
    ];
}
