<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\User;
class Paymentez extends Model
{
    //
    protected $table ='paymentez';
    protected $fillable = [
		'user_id',
        'id_transaction',
        'holder_name',
        'email',
        'number_card',
        'amount',
        'message',
        'status',
        'order_description',
        'clase_id',
        'tarea_id',
        'combo_id',
        'paymentez_card',
        'paymentez_transaction',
        'estado'
    ];
    protected $private = [
        'id_transaction',
        'number_card',
    ];
}
