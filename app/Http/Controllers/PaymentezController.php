<?php


namespace App\Http\Controllers;

use App\Http\Controllers\BaseController as BaseController;
use http\Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\User;
use App\Paymentez;
use App\Transaction;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Middleware;
use App\BemteUtilities;
use App\Alumno;
use App\Tarea;
use App\Clase;
use App\AlumnoPago;
use App\AlumnoCompra;
use Illuminate\Support\Facades\Mail;
use App\Mail\Notificacion;
use DateTime;

use Carbon\Carbon;

class PaymentezController extends BaseController
{
    public $estadosString = [
        "Esperando para ser Pagada.",
        "Se requiere verificación, por favor revise la sección de Verificar",
        "",
        "Pagada",
        "",
        "",
        "Fraude",
        "Eeverso",
        "Contracargo",
        "Rechazada por el procesador",
        "Error en el sistema",
        "Fraude detectado por Paymentez",
        "Blacklist de Paymentez",
        "Tiempo de tolerancia",
        "Expirada por Paymentez",
        "",
        "",
        "",
        "",
        "Código de autorización invalido",
        "Código de autorización expirado",
        "Fraude Paymentez - Reverso pendiente",
        "AuthCode Inválido - Reverso pendiente",
        "AuthCode Expirado - Reverso pendiente",
        "Fraude Paymentez - Reverso solicitado",
        "AuthCode Inválido - Reverso solicitado",
        "AuthCode Expirado - Reverso solicitado",
        "Comercio - Reverso pendiente",
        "Comercio - Reverso solicitado",
        "Anulada",
        "Transacción asentada (solo para Ecuador)",
        "Esperando OTP",
        "OTP validado correctamente",
        "OTP no validado",
        "Reverso parcial",
        "Método 3DS solicitado, esperando para continuar",
        "Desafío 3DS solicitado, esperando el CRES",
        "Rechazada por 3DS",
    ];

    public function inicioTransaccion(Request $request){
        try {

            $inicio = Transaction::create([
                'user_id' => $request->user_id,
                'clase_id' => isset($request->clase_id) ? $request->clase_id : NULL,
                'tarea_id' => isset($request->tarea_id) ? $request->tarea_id : NULL,
                'combo_id' => isset($request->combo_id) ? $request->combo_id : NULL,
                'estado' => 'Inicio'
            ]);
            if ($inicio){
                return $this->sendResponse('Success','Inicio de transacción', $inicio->id);
            }
        } catch (Exception $e) {
            $message = 'No se puede realizar la transacción';
            return $this->sendError($message ,'Algo ha sucedido, contáctese con el administrador del sistema', 404);
        }
    }

    public function finTransaccion(Request $request){
        try {

            $transactionData = null;
            $validateUser = User::where('id', $request->user_id)->first();
            $id_transaction = Transaction::where('user_id', $request->user_id)->where('estado', 'Inicio')->first();
            //PAGO POR TARJETA
            $date = new DateTime();
            $unix_timestamp = $date->getTimestamp();
            $debitData = [
                'transaction' => [
                    'status' => 1,
                    'amount' => (float)$request->total,
                    'order_description' => $request->description,
                    'dev_reference' => $request->holder_name . '-' . $unix_timestamp,
                    'installments' => 1,
                    'id' => $request->id,
                    'status_detail' => $request->status_detail,
                ],
                "card" => [
                    "holder_name" => $validateUser->name,
                    "number" => $request->number_card
                ],
                "user" => [
                    "id" => $validateUser->id,
                    "email" => $validateUser->email
                ]
            ];
            $debitUrl = ' https://portal-bemte/paymentez/webhook';
            // $debitUrl = Bemtetilities::getUrl() . 'transaction/debit';
            // try {
            //     $client = new Client();
            //     $authToken = BemteUtilities::getAuthToken();
            //     $response = $client->post((string)$debitUrl, [
            //         'headers' => ['Content-Type' => 'application/json'/*, 'Auth-Token' => $authToken*/],
            //         'body' => json_encode($debitData),
            //     ]);
            // } catch (BadResponseException $e) {
            //     $message = 'Error con el medio de pago';
            //     return $this->sendError($message.''.$e ,'Algo ha sucedido, contactese con el administrador del sistema', 404);
            // }
            $inf = json_encode($debitData);
            $transactionInf = json_decode($inf);
            // $transactionInf = json_decode($response->getBody());
            if (!$transactionInf) {
                $message = 'Error al realizar la transacción';
                return $this->sendError($message ,'Algo ha sucedido, contactese con el administrador del sistema', 404);
            }
            if (strcmp($transactionInf->transaction->status, 'success') != 0) {
                if (strcmp($transactionInf->transaction->status, 'pending') == 0) {
                    $message = 'Error '.$this->estadosString[$transactionData->transaction->status_detail].'-'. $transactionData->transaction;
                    return $this->sendError($message ,'Algo ha sucedido, contactese con el administrador del sistema', 404);
                }
                // $message = 'El pago ha sido rechazado';
                // return $this->sendError($message ,'Algo ha sucedido, contactese con el administrador del sistema', 404);
            }


            // $userInformation = json_decode($transactionInf->user);
            // $transactionData = json_decode($transactionInf->transaction);
            // $card = json_decode($transactionInf->card);

            $paymentez =  Paymentez::create([
                'user_id' => $validateUser->id,
                'id_transaction' => $request->id,
                'holder_name' => $request->holder_name,
                'email' => $validateUser->email,
                'number_card' => $request->number_card,
                'amount' => (float)$request->total,
                'message' => null,
                'status' => 1,
                'order_description' => $request->description,
                'clase_id' => isset($request->clase_id) ? $request->clase_id : NULL,
                'tarea_id' => isset($request->tarea_id) ? $request->tarea_id : NULL,
                'combo_id' => isset($request->combo_id) ? $request->combo_id : NULL,
                'paymentez_card' => isset($request->paymentez_card) ? $request->paymentez_card : NULL,
                'paymentez_transaction' => isset($request->paymentez_transaction) ? $request->paymentez_transaction : NULL,
                'estado' => 'Pagado'
            ]);

            if($paymentez){
                //RETORNAR MENSAJE DE EXITOSO
                Transaction::where('id', $paymentez->id_transaction)
                    ->update(['estado' =>  'Finalizado']);

                $tarea = null;
                if ($request['tarea_id'] > 0)
                {
                    if (($request['clase_id'] > 0) || ($request['combo_id'] != '0'))
                    {
                        return response()->json(['error' => 'Especifique una opción para la tarea'], 401);
                    }
                    $tarea = Tarea::where('id', $request['tarea_id'])->first();
                    if ($tarea == null)
                    {
                        return response()->json(['error' => 'No existe Tarea a pagar'], 401);
                    }
                    // else if ($tarea->estado != 'Confirmado')
                    // {
                    //     return response()->json(['error' => 'Tarea no Confirmada para pagar'], 401);
                    // }
                }
                $clase = null;
                if ($request['clase_id'] > 0)
                {
                    if ($request['tarea_id'] > 0 || ($request['combo_id'] != '0'))
                    {
                        return response()->json(['error' => 'Especifique una opción para la clase'], 401);
                    }
                    $clase = Clase::where('id', $request['clase_id'])->first();
                    if ($clase == null)
                    {
                        return response()->json(['error' => 'No existe Clase a pagar'], 401);
                    }
                    // else if ($clase->estado != 'Confirmado')
                    // {
                    //     return response()->json(['error' => 'Clase no Confirmada para pagar'], 401);
                    // }
                }
                $combo = null;
                if ($request['combo_id'] != '0')
                {
                    if ($request['tarea_id'] > 0 || $request['clase_id'] > 0)
                    {
                        return response()->json(['error' => 'Especifique una opción para el Combo'], 401);
                    }
                    else
                    {
                        $combo = AlumnoCompra::where('id', $request['combo_id'])->where('user_id', $request['user_id'])->first();
                        if ($combo != null && $combo->estado != 'Solicitado')
                        {
                            return response()->json(['error' => 'La Solicitud ya fue procesada'], 401);
                        }
                    }
                } 
                $user = Alumno::where('user_id', $request['user_id'])->first();
                if ($user != null)
                {
                    if ($user->activo)
                    {

                        $correoAdmin = 'Ha sido realizado un pago por tarjeta de crédito por '.$user->nombres.' '.$user->apellidos;
                        $detalle = '';
                        
                        if ($clase != null)
                        {
                            $dataClase['estado'] = 'Confirmado';
                            $actualizado = Clase::where('id', $clase->id )->update( $dataClase );
                            $detalle = 'Para la Clase de '.$clase->materia.', '.$clase->tema
                                        .', para el '.$clase->fecha.' a las '.$clase->hora1;
                            if(!$actualizado )
                            {
                                return response()->json(['error' => 'Error al actualizar solicitud'], 401);
                            }
                        }
                        if ($tarea != null)
                        {
                            $dataTarea['estado'] = 'Confirmado';
                            $actualizado = Tarea::where('id', $tarea->id )->update( $dataTarea );
                            $detalle = 'Para la Tarea de '.$tarea->materia.', '.$tarea->tema
                                        .', para el '.$tarea->fecha_entrega;
                            if(!$actualizado )
                            {
                                return response()->json(['error' => 'Error al actualizar solicitud'], 401);
                            }
                        }

                        try 
                        {
                            Mail::to(env('MAILADMIN'))->send(new Notificacion(
                                    'Administrador de '.env('EMPRESA'), 
                                    $correoAdmin, $detalle, 'Por favor, revisar el pago.', 
                                    env('EMPRESA')));
                        }
                        catch (Exception $e) { }

                        return $this->sendResponse('Success','Compra realizada correctamente', $paymentez->id);

                    }
                    else
                    {
                        return response()->json(['error' => 'Alumno Inactivo'], 401);
                    }
                }
                else
                {
                    return response()->json(['error' => 'Sin Alumno para subir Transferencia'], 401);
                }

            } else {
                Paymentez::where('id_transaction', $paymentez->id_transaction)
                ->update(['estado' =>  'Cancelado']);

                Transaction::where('id', $paymentez->id_transaction)
                    ->update(['estado' =>  'Cancelado']);

                $message = 'Compra no realizada';
                return $this->sendError($message ,'Algo ha sucedido, contáctese con el administrador del sistema', 404);
            }
            
        } catch (Exception $e) {
            $message = 'Compra no realizada';
            return $this->sendError($message ,'Algo ha sucedido, contactese con el administrador del sistema', 404);
        }
    }

}
