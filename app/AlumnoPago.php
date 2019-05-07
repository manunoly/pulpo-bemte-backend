<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/*

Solicitado
Cancelado
Aprobado
Rechazado

*/
class AlumnoPago extends Model
{
    protected $table = 'alumno_pago';

    protected $fillable = [
        'user_id', 'combo_id', 'tarea_id', 'clase_id', 'archivo', 'drive', 'estado'
    ];
}