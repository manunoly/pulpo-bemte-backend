<?php


namespace App\Http\Controllers;

use App\Http\Controllers\BaseController as BaseController;
use http\Exception;
use Mail;
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
            // $url = ' https://portal-bemte/paymentez/webhook';

            $transactionData = null;
            $validateUser = User::where('id', $request->user_id)->first();
            $id_transaction = Transaction::where('user_id', $request->user_id)->where('estado', 'Inicio')->first();
            //PAGO POR TARJETA
            $date = new DateTime();
            $unix_timestamp = $date->getTimestamp();
            $debitData = [
                // "transaction": {
                //     "status": 1,
                //     "order_description": "ORDER #1507155336536",
                //     "authorization_code": "113310",
                //     "status_detail": 3,
                //     "date": "04/10/2017 22:15:37",
                //     "message": "Operation Successful",
                //     "id": "CI-502",
                //     "dev_reference": "1507155336536",
                //     "carrier_code": "6",
                //     "amount": 10.5,
                //     "paid_date": "04/10/2017 19:15:00",
                //     "installments": 1,
                //     "stoken": "e03f67eba6d730d8468f328961ac9b2e",
                //     "application_code": "AndroidTest"
                //  },
                //  "user": {
                //     "id": "4",
                //     "email": "user@example.com"
                //  },
                //  "card": {
                //     "bin": "411111",
                //     "holder_name": "Martin Mucito",
                //     "type": "vi",
                //     "number": "1111",
                //     "origin": "ORIGIN"
                //  }
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
                'estado' => 'Solicitado'
            ]);

            if($paymentez){
                    //RETORNAR MENSAJE DE EXITOSO
                    Paymentez::where('id_transaction', $paymentez->id_transaction)
                    // ->join('transactions', 'transactions.id', '=', 'paymentez.id')
                    ->update(['estado' =>  'Pagado']);

                    // Transaction::where('created_at', $paymentez->created_at)
                    // // ->join('transactions', 'transactions.id', '=', 'paymentez.id')
                    // ->update(['estado' =>  'Finalizado']);

                    return $this->sendResponse('Success','Compra realizada correctamente', $paymentez->id);
            } else {
                Paymentez::where('id_transaction', $paymentez->id_transaction)
                // ->join('transactions', 'transactions.id', '=', 'paymentez.id')
                ->update(['estado' =>  'Cancelado']);
                // Transaction::where('created_at', $paymentez->created_at)
                //     // ->join('transactions', 'transactions.id', '=', 'paymentez.id')
                //     ->update(['estado' =>  'Cancelado']);
                $message = 'Compra no realizada';
                return $this->sendError($message ,'Algo ha sucedido, contáctese con el administrador del sistema', 404);
            }
            
        } catch (Exception $e) {
            $message = 'Compra no realizada';
            return $this->sendError($message ,'Algo ha sucedido, contactese con el administrador del sistema', 404);
        }
    }

}
