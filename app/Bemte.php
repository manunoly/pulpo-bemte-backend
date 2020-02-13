<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Bemte extends Model
{
    protected $table = 'bemte';

    protected $fillable = [
        'terminosNombre', 'reglamentoNombre', 'videoNombre', 
        'terminosDescripcion', 'reglamentoDescripcion', 'videoDescripcion', 
        'terminosUrl', 'reglamentoUrl', 'videoUrl', 'valorTarea',
    ];
}