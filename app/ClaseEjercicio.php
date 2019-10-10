<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Auth;

class ClaseEjercicio extends Model
{
    protected $table = 'clase_ejercicio';   

    protected $fillable = [
        'clase_id', 'archivo', 'drive',
    ];

    public function __construct(){
        $this->user_id = Auth::user()->id;
    }
}