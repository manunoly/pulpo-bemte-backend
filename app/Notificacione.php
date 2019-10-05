<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Notificacione extends Model
{
    protected $table = 'notificaciones'; 

    protected $fillable = [
        'id', 'user_id', 'created_at', 'titulo', 'notificacion', 'estado', 'leida',
        'tarea_id', 'clase_id', 'chat_id', 'compra_id',
    ];
}