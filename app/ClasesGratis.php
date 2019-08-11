<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ClasesGratis extends Model
{
    protected $table = 'clases_gratis';

    protected $fillable = [
        'nombre', 'descripcion', 'url', 'activa',
    ];
}