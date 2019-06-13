<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class AlumnoBilletera extends Model
{
    protected $table = 'alumno_billetera';
    
    protected $fillable = [
        'user_id', 'combo', 'horas'
    ];
}