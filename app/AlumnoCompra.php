<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/*  ESTADOS

Solicitado
Aprobado
Rechazado

*/

class AlumnoCompra extends Model
{
    protected $table = 'alumno_compra';

    protected $fillable = [
        'user_id', 'combo', 'valor', 'estado', 
    ];
}