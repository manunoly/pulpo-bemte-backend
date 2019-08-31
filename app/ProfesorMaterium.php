<?php

namespace App;

use Illuminate\Database\Eloquent\Model;


class ProfesorMaterium extends Model
{
    protected $table = 'profesor_materia';    

    protected $fillable = [
        'user_id', 'materia', 'activa',
    ];
}
