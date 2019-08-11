<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Pago extends Model
{
    protected $table = 'pagos'; 

    protected $fillable = [
        'id', 'user_id', 'created_at', 'tarea_id', 'clase_id', 'valor', 'horas', 'estado',
    ];
}