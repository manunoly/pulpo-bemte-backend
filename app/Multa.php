<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Multa extends Model
{
    protected $table = 'multas'; 

    protected $fillable = [
        'id', 'user_id', 'created_at', 'valor', 'comentario',
    ];
}