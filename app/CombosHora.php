<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CombosHora extends Model
{
    protected $table = 'combos_horas'; 

    protected $fillable = [
        'id', 'combo', 'hora', 'inversion', 'descuento', 'activo',
    ];
}