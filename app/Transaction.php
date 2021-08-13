<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    //
    protected $table ='transactions';
    protected $fillable = [
		'user_id',
        'clase_id',
        'tarea_id',
        'combo_id',
        'estado'
    ];
}