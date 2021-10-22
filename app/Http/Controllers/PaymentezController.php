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
use App\CombosHora;
use App\Profesore;
use App\Pago;
use Illuminate\Support\Facades\Mail;
use App\Mail\Notificacion;
use DateTime;
use App\NotificacionesPushFcm;
use App\Mail\NotificacionClases;
use App\Mail\NotificacionTareas;

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
            $validateUser = Alumno::where('user_id', $request->user_id)->first();
            // $id_transaction = Transaction::where('user_id', $request->user_id)->where('estado', 'Inicio')->first();
            //PAGO POR TARJETA
            // $date = new DateTime();
            // $unix_timestamp = $date->getTimestamp();
            // $debitData = [
            //     'transaction' => [
            //         'status' => 1,
            //         'amount' => (float)$request->total,
            //         'order_description' => $request->description,
            //         'dev_reference' => $request->holder_name . '-' . $unix_timestamp,
            //         'installments' => 1,
            //         'id' => $request->id,
            //         'status_detail' => $request->status_detail,
            //     ],
            //     "card" => [
            //         "holder_name" => $validateUser->name,
            //         "number" => $request->number_card
            //     ],
            //     "user" => [
            //         "id" => $validateUser->id,
            //         "email" => $validateUser->email
            //     ]
            // ];
            // $debitUrl = ' https://portal-bemte/paymentez/webhook';
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
            // $inf = json_encode($debitData);
            // $transactionInf = json_decode($inf);
            // // $transactionInf = json_decode($response->getBody());
            // if (!$transactionInf) {
            //     $message = 'Error al realizar la transacción';
            //     return $this->sendError($message ,'Algo ha sucedido, contactese con el administrador del sistema', 404);
            // }
            // if (strcmp($transactionInf->transaction->status, 'success') != 0) {
            //     if (strcmp($transactionInf->transaction->status, 'pending') == 0) {
            //         $message = 'Error '.$this->estadosString[$transactionData->transaction->status_detail].'-'. $transactionData->transaction;
            //         return $this->sendError($message ,'Algo ha sucedido, contactese con el administrador del sistema', 404);
            //     }
            //     // $message = 'El pago ha sido rechazado';
            //     // return $this->sendError($message ,'Algo ha sucedido, contactese con el administrador del sistema', 404);
            // }


            // $userInformation = json_decode($transactionInf->user);
            // $transactionData = json_decode($transactionInf->transaction);
            // $card = json_decode($transactionInf->card);
            $inf = json_decode($request->paymentez_transaction);

            $paymentez =  Paymentez::create([
                'user_id' => $validateUser->user_id,
                'id_bemte' => $request->id,
                'id_transaction' => $inf->id,
                'holder_name' => $request->holder_name,
                'email' => $validateUser->correo,
                'number_card' => $request->number_card,
                'amount' => (float)$request->total,
                'message' => $inf->status,
                'status' => $inf->status_detail,
                'order_description' => $request->description,
                'clase_id' => isset($request->clase_id) ? $request->clase_id : NULL,
                'tarea_id' => isset($request->tarea_id) ? $request->tarea_id : NULL,
                'combo_id' => isset($request->combo_id) ? $request->combo_id : NULL,
                'paymentez_card' => isset($request->paymentez_card) ? $request->paymentez_card : NULL,
                'paymentez_transaction' => isset($request->paymentez_transaction) ? $request->paymentez_transaction : NULL,
                'estado' => 'Pagado'
            ]);

            if($paymentez){
                if($paymentez->status != 3){
                    Paymentez::where('id_transaction', $paymentez->id_transaction)
                    ->update(['estado' =>  $inf->status]);

                    Transaction::where('id', $paymentez->id_transaction)
                        ->update(['estado' =>  $inf->status]);

                    $message = $inf->status;
                    return $this->sendError($message ,'. Algo ha sucedido, contáctese con el administrador del sistema', 404);
                
                }
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
                    else if ($tarea->estado != 'Confirmado')
                    {
                        return response()->json(['error' => 'Tarea no Confirmada para pagar'], 401);
                    }
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
                    else if ($clase->estado != 'Confirmado')
                    {
                        return response()->json(['error' => 'Clase no Confirmada para pagar'], 401);
                    }
                }
                $combo = null;
                if ($request['combo_id'] != '0')
                {
                    if ($request['tarea_id'] > 0 || $request['clase_id'] > 0)
                    {
                        return response()->json(['error' => 'Especifique una opción para el Combo'], 401);
                    }
                    if (is_numeric($request['combo_id']))
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
                        $inf = json_decode($paymentez->paymentez_transaction);
                        $correoAdmin = 'Se ha sido realizado un pago por tarjeta de crédito por '.$user->nombres.' '.$user->apellidos;
                        $detalle = 'Transacción ID: '.$inf->id. ' Authorization Code: '.$inf->authorization_code.' Valor: '.$paymentez->amount;

                        $duracion = 0;
                        $compra = null;

                        if ($request['combo_id'] != '0' && $combo == null)
                        {
                            $horas = CombosHora::where('descuento', $request['total'])->first();
                            if ($horas){
                                $combo = AlumnoCompra::create([
                                    'user_id' => $request['user_id'],
                                    'combo' => $request['combo_id'],
                                    'valor' => $request['total'],
                                    'horas' => $horas['hora'],
                                    'estado' => 'Aceptado'
                                ]);
                                if (!$combo->id)
                                {
                                    return response()->json(['error' => 'Error al registrar solicitud!'], 401);
                                }
                            }

                            if ($compra == null)
                                $compra = AlumnoCompra::where('id', $combo->id)->first();
                            
                            
                        }
                        $solicitud = AlumnoPago::where('user_id', $request['user_id'])
                                                ->where('tarea_id', $request['tarea_id'])
                                                ->where('clase_id', $request['clase_id'])
                                                ->where('combo_id', $combo != null ? $combo->id : $request['combo_id'])->first();
                        if ($solicitud == null)
                        {
                            $aplica = AlumnoPago::create([
                                'user_id' => $request['user_id'],
                                'tarea_id' => $request['tarea_id'],
                                'clase_id' => $request['clase_id'],
                                'combo_id' => $combo != null ? $combo->id : $request['combo_id'],
                                'archivo' => 'Credit Card',
                                'drive' => null,
                                'estado' => 'Aprobado'
                            ]);
                            if (!$aplica->id)
                            {
                                return response()->json(['error' => 'Error al registrar solicitud!'], 401);
                            }
                        }

                        if ($clase != null)
                        {
                            $duracion = $clase->duracion + ($clase->personas - 1);
                            $dataAct['estado'] = 'Aceptado';
                            if ($clase->compra_id > 0)
                                $compra = AlumnoCompra::where('id', $clase->compra_id )->first();
                                $actualizarCompra = AlumnoCompra::where('id', $compra->id)->update( $dataAct );
                            $dataClase['estado'] = 'Aceptado';
                            $actualizado = Clase::where('id', $clase->id )->update( $dataClase );
                            $detalle = ' para la Clase de '.$clase->materia.', '.$clase->tema
                                        .', para el '.$clase->fecha.' a las '.$clase->hora1;
                            if(!$actualizado )
                            {
                                return response()->json(['error' => 'Error al actualizar solicitud'], 401);
                            }
                        }
                        if ($tarea != null)
                        {
                            $duracion = $tarea->tiempo_estimado;
                            $dataAct['estado'] = 'Aceptado';
                            if ($tarea->compra_id > 0)
                                $compra = AlumnoCompra::where('id', $tarea->compra_id )->first();
                                $actualizarCompra = AlumnoCompra::where('id', $tarea->compra_id)->update( $dataAct );

                            $dataTarea['estado'] = 'Aceptado';
                            $actualizado = Tarea::where('id', $tarea->id )->update( $dataTarea );
                            $detalle = ' para la Tarea de '.$tarea->materia.', '.$tarea->tema
                                        .', para el '.$tarea->fecha_entrega;
                            if(!$actualizado )
                            {
                                return response()->json(['error' => 'Error al actualizar solicitud'], 401);
                            }
                        }

                        $billetera = Alumno::where('user_id', $request['user_id'])->first();
                        if ($billetera == null)
                        {
                            $messages["error"] = 'El Alumno no existe';
                            return response()->json(['error' => 'El Alumno no existe'], 401);
                        }

                        if ($compra != null)
                        {
                            if ($billetera->billetera + $compra->horas - $duracion < 0)
                            {
                                $messages["error"] = 'La Horas compradas del combo no son suficientes para pagar';
                                return response()->json(['error' => 'La Horas compradas del combo no son suficientes para pagar'], 401);
                            }
                        }

                        if ($tarea != null)
                        {
                            
                            $profeTarea = Profesore::where('user_id', $tarea->user_id_pro)->first();
                            $pagoProf = Pago::create([
                                    'user_id' => $tarea->user_id_pro,
                                    'tarea_id' => $tarea->id,
                                    'clase_id' => 0,
                                    'valor' => $duracion * $profeTarea->valor_tarea,
                                    'horas' => $duracion,
                                    'estado' => 'Solicitado',
                                    'valorTotal' => 0,
                                    'calculoValor' => 0,
                                    'valorPendiente' => 0
                                    ]);
                            if (!$pagoProf->id)
                            {
                                $messages["error"] = 'Ocurrió un error al crear Pago al Profesor';
                                return response()->json(['error' => 'Ocurrió un error al crear Pago al Profesor'], 401);

                            }
                            
                            $userAlumno =  User::where('id', $tarea->user_id)->first();
                            $userProf = User::where('id', $tarea->user_id_pro)->first();
                            $inf = json_decode($paymentez->paymentez_transaction);

                            try {
                                //enviar notificacion al profesor y al alumno
                                $notificacion['titulo'] = 'Tarea Aprobada';
                                $notificacion['estado'] = 'NO';
                                $notificacion['tarea_id'] = $tarea->id;
                                $notificacion['clase_id'] = 0;
                                $notificacion['chat_id'] = 0;
                                $notificacion['compra_id'] = 0;
                                $pushClass = new NotificacionesPushFcm();
                                $notificacion['color'] = "profesor";
                                $notificacion['texto'] = 'La Tarea de '.$tarea->materia.', '.$tarea->tema.', ha sido Confirmada.';
                                $pushClass->enviarNotificacion($notificacion, $userProf);
                        
                            } catch (Exception $e) { }

                            try 
                            {
                                Mail::to($userAlumno->email)->send(new NotificacionTareas($tarea ?? 'Tarea',  $inf->id ?? 'ID', $inf->authorization_code ?? 'Code', $paymentez->amount ?? 'Monto', $userAlumno->name ?? 'Alumno', $userProf->name ?? 'Profesor', 
                                                                env('EMPRESA'), true));
                                Mail::to($userProf->email)->send(new NotificacionTareas($tarea ?? 'Tarea', $inf->id ?? 'ID', $inf->authorization_code ?? 'Code', $paymentez->amount ?? 'Monto', $userAlumno->name ?? 'Alumno', $userProf->name ?? 'Profesor', 
                                                                env('EMPRESA'), false));
                            }
                            catch (Exception $e) 
                            { };

                        }                            
                        if ($clase != null)
                        {
                            
                            //$dataAct['estado'] = 'Pago_Aprobado';
                            $profeClase = Profesore::where('user_id', $clase->user_id_pro)->first();
                            $pagoProf = Pago::create([
                                    'user_id' => $clase->user_id_pro,
                                    'clase_id' => $clase->id,
                                    'tarea_id' => 0,
                                    'valor' => ($clase->duracion + ($clase->personas - 1)) * $profeClase->valor_clase,
                                    'horas' => $clase->duracion,
                                    'estado' => 'Solicitado',
                                    'valorTotal' => 0,
                                    'calculoValor' => 0,
                                    'valorPendiente' => 0
                                    ]);
                            if (!$pagoProf->id)
                            {
                                $messages["error"] = 'Ocurrió un error al crear Pago al Profesor';
                                return response()->json(['error' => 'Ocurrió un error al crear Pago al Profesor'], 401);

                            }
                            
                            // $userAlumno = Alumno::where('user_id', $clase->user_id)->first();
                            // $userProf = Profesore::where('user_id', $clase->user_id_pro)->first();
                            $userAlumno =  User::where('id', $clase->user_id)->first();
                            $userProf = User::where('id', $clase->user_id_pro)->first();
                            $inf = json_decode($paymentez->paymentez_transaction);
                            
                            try {

                                //enviar notificacion al profesor y al alumno
                                $notificacion['titulo'] = 'Clase Aprobada';
                                $notificacion['estado'] = 'NO';
                                $notificacion['clase_id'] = $clase->id;
                                $notificacion['tarea_id'] = 0;
                                $notificacion['chat_id'] = 0;
                                $notificacion['compra_id'] = 0;
                                $pushClass = new NotificacionesPushFcm();
                                $notificacion['color'] = "profesor";
                                $notificacion['texto'] = 'La Clase de '.$clase->materia.', '.$clase->tema.', ha sido Confirmada.';

                                $pushClass->enviarNotificacion($notificacion, $userProf);

                            }catch ( Exception $e ) {

                            }

                            try 
                            {
                                Mail::to($userAlumno->email)->send(new NotificacionClases($clase ?? 'Clase', 
                                        $inf->id ?? 'ID',
                                        $inf->authorization_code ?? 'Code', 
                                        $paymentez->amount ?? 'Monto', 
                                        $userAlumno->nombres ?? 'Alumno Nombre', 
                                        $userProf->name ?? 'Profesor Nombre', 
                                        env('EMPRESA'), true));
                                Mail::to($userProf->email)->send(new NotificacionClases($clase ?? 'Clase', 
                                        $inf->id ?? 'ID', 
                                        $inf->authorization_code ?? 'Code', 
                                        $paymentez->amount ?? 'Monto', 
                                        $userAlumno->nombres ?? 'Alumno Nombre', 
                                        $userProf->name ?? 'Profesor Nombre', 
                                        env('EMPRESA'), false));
                            }
                            catch (Exception $e) 
                            { } 
                              
                        }
                        if ($compra != null)
                        {
                            $dataBill['billetera'] = $billetera->billetera + $compra->horas - $duracion;
                            $actualizado = Alumno::where('user_id', $billetera->user_id)->update( $dataBill );
                            if(!$actualizado )
                            {
                                $messages["error"] = 'Ocurrió un error al actualizar Billetera';
                                return response()->json(['error' => 'Ocurrió un error al actualizar Billetera'], 401);
                            }

                            // if ($request['combo_id'] != '0') {
                                $userAlumno = User::where('id', $paymentez->user_id)->first();
                                $inf = json_decode($paymentez->paymentez_transaction);
                                try 
                                {
                                    Mail::to($userAlumno->email)->send(new Notificacion(
                                        $userAlumno->name ?? 'Estimado', 
                                        'Su compra de combo de ' .$combo->horas ?? '-1' .' horas por el valor de '.$combo->valor ?? '-1'.' con tarjeta de crédito, se ha realizado con éxito,',
                                        'Transacción ID: '.$inf->id ?? 'ID'. ' Authorization code: '.$inf->authorization_code ?? 'Code',
                                         '', 
                                        env('EMPRESA')));
                                }
                                catch (Exception $e) { }
                            // }
                            
                            //enviar notificacion al alumno
                            // $userAlumno = Alumno::where('user_id', $compra->user_id)->first();
                            // $notificacion['titulo'] = 'Pago Horas Aprobado';
                            // $notificacion['texto'] = 'El pago de '.$compra->horas.' Horas ha sido '.$request['estado'].'. Por favor,';
                            // $notificacion['color'] = "alumno";
                            // $notificacion['texto'] = $notificacion['texto'].' revise su Billetera';

                            // $notificacion['estado'] = 'NO';
                            // $notificacion['clase_id'] = 0;
                            // $notificacion['tarea_id'] = 0;
                            // $notificacion['chat_id'] = 0;
                            // $notificacion['compra_id'] = $compra->id;
                            // $pushClass = new NotificacionesPushFcm();
                            // $pushClass->enviarNotificacion($notificacion, $userAlumno);
                        }

                        try 
                        {
                            Mail::to(env('MAILADMIN'))->send(new Notificacion(
                                    'Administrador de '.env('EMPRESA'), 
                                    $correoAdmin, $detalle ?? 'Pago tarjeta', 'Por favor, revisar el pago.', 
                                    env('EMPRESA')));
                        }
                        catch (Exception $e) { }

                        // return true;
                        return $this->sendResponse('Success','Compra realizada correctamente', 0);

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
