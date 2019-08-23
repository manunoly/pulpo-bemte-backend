<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Chat extends Model
{
    protected $table = 'chat';

    protected $fillable = [
        'user_id', 'user_id_pro', 'texto', 'imagen', 'leido', 'tarea_id', 'clase_id', 'user_escribe', 
    ];
}