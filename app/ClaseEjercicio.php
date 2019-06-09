<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ClaseEjercicio extends Model
{
    protected $table = 'clase_ejercicio';   

    protected $fillable = [
        'user_id', 'clase_id', 'archivo', 'drive',
    ];
}