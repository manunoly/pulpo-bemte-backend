<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Auth;

class TareaEjercicio extends Model
{
    protected $table = 'tarea_ejercicio';   

    protected $fillable = [
        'tarea_id', 'archivo', 'drive',
    ];

    public function __construct(){
        $this->user_id = Auth::user()->id;
    }
}