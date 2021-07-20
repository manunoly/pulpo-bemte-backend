<?php


namespace App\Http\Controllers;

use App\Http\Controllers\API\BaseController as BaseController;
use http\Exception;
use Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\User;
use App\AlumnoCompra;
use App\Buy;
use App\Cart;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Middleware;
use Intervention\Image\ImageManagerStatic as Image;
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
    public function verifyTransaction(Request $request){
        try {
            $data = $request['data'];
            $debitUrl = BemteUtilities::getUrl() . 'transaction/verify';
            try {
                $data['type'] = 'BY_OTP';
                $data['more_info'] = true;
                $client = new Client();
                $authToken = BemteUtilities::getAuthToken();
                $response = $client->post((string)$debitUrl, [
                    'headers' => ['Content-Type' => 'application/json', 'Auth-Token' => $authToken],
                    'body' => json_encode($data),
                ]);
            } catch (BadResponseException $e) {
                return response()->json(Msg::responseMsg('Error al validar el código', 'error', $e->getMessage()), 404);
            }
            $transaFuckingData = json_decode($response->getBody());
            //sendMail
            //concat arr products ids
            if (!$transaFuckingData) {
                return response()->json(Msg::responseMsg('Error al realizar la transacción', 'error', 404, false), 404);
            }
            if (strcmp($transaFuckingData->transaction->status, 'success') != 0) {
                return response()->json(Msg::responseMsg(
                    'Error en la verificación',
                    'error', 404,
                    $this->estadosString[$transaFuckingData->transaction->status_detail]),
                    404);
            }
        } catch (Exception $e) {
            return response()->json(Msg::responseMsg($e, 'error', false, 500), 500);
        }
    }

    public function newBuy(Request $request){
        try {
            //Transactional Paymentez
            $transactionData = null;
            $storagePath = null;
                //PAGO POR TARJETA
                $date = new DateTime();
                $unix_timestamp = $date->getTimestamp();
                $debitData = [
                    'order' => [
                        'amount' => (float)$request['totalPagar'],
                        'description' => $resultDesc,
                        'dev_reference' => $user->name . '-' . $unix_timestamp,
                        'vat' => (float)$request['iva'],
                        'installments' => 1,
                        'tax_percentage' => 12, 
                        'taxable_amount' =>  (float)$request['subtotal']
                    ],
                    "card" => ["token" => $request['tokenCard']],
                    "user" => [
                        "id" => $user->name,
                        "email" => $user->email
                    ]
                ];

                $debitUrl = BemteUtilities::getUrl() . 'transaction/debit';
                try {
                    $client = new Client();
                    $authToken = BemteUtilities::getAuthToken();
                    $response = $client->post((string)$debitUrl, [
                        'headers' => ['Content-Type' => 'application/json', 'Auth-Token' => $authToken],
                        'body' => json_encode($debitData),
                    ]);
                } catch (BadResponseException $e) {
                    return response()->json(Msg::responseMsg('Error con el medio de pago', 'error', 404), 404);
                }
                $transactionData = json_decode($response->getBody());
                if (!$transactionData) {
                    return response()->json(Msg::responseMsg('Error al realizar la transacción', 'error', 404, false), 404);
                }
                if (strcmp($transactionData->transaction->status, 'success') != 0) {
                    if (strcmp($transactionData->transaction->status, 'pending') == 0) {
                        return response()->json(Msg::responseMsg(
                            $this->estadosString[$transactionData->transaction->status_detail],
                            'error', 404,
                            $transactionData->transaction),
                            404);
                    }
                    return response()->json(Msg::responseMsg(
                        'El pago ha sido rechazado',
                        'error', 404,
                        $this->estadosString[$transactionData->transaction->status_detail]),
                        404);
                }
            return response()->json(Msg::responseMsg('Compra realizada correctamente', 'error', $mailData, 'En un momento recibirás un correo de confirmación'), 500);

        } catch (Exception $e) {
            return response()->json(Msg::responseMsg($e, 'error', false, 500), 404);
        }
    }

    public function refund($idCompra){
        $refundUrl = BemteUtilities::getUrl() . 'transaction/refund';
        try {
            $compra = Buy::where('id', $idCompra)->first();
            if ($compra) {
                try {
                    $transaction = json_decode($compra->transaction);
                    $transactionData = [
                        "transaction" => [
                            "id" => $transaction->id
                        ],
                    ];
                    $client = new Client();
                    $authToken = BemteUtilities::getAuthToken();
                    if (BemteUtilities::getAuthToken()) {
                        $response = $client->post($refundUrl, [
                            'headers' => ['Content-Type' => 'application/json', 'Auth-Token' => $authToken],
                            'body' => json_encode($transactionData)
                        ]);
                    } else {
                        return response()->json(Msg::responseMsg('No se realizó el reembolzo', 'error', 'Error'), 500);
                    }
                } catch (BadResponseException $e) {
                    //catch the pay error
                    return response()->json(Msg::responseMsg('No se realizó el reembolzo', 'error', $e->getMessage()), 500);
                }
                $transactionData = json_decode($response->getBody());
                if (strcmp($transactionData->status, 'success') == 0) {
                    return response()->json(Msg::responseMsg('Compra reembolzada', 'ok', true, true), 202);
                } else {
                    return response()->json(Msg::responseMsg('Reembolzo rechazado', 'error', 500, false), 500);
                }
            } else {
                return response()->json(Msg::responseMsg('No se encontró la referencia de la compra', 'error', 500, false), 500);
            }
        } catch (Exception $e) {
            return response()->json(Msg::responseMsg($e, 'error', false, 500), 500);
        }
    }

    public function getCards(Request $request){
        try {
            if($request->user_id){
                //VALIDAR QUE EL USUARIO ESTE REGISTRADO
                $validateUser = User::where('id', $request->user_id)->first();
                if($validateUser){
                    $client = new Client();
                    $url  = BemteUtilities::getUrl().'card/list?uid='.$validateUser->id;
                    $authToken = BemteUtilities::getAuthToken();
                    $response = $client->request('GET', trim($url), [
                        'headers' => [
                            'Accept'       => 'application/json',
                            'Content-Type' => 'application/json',
                            'Auth-Token'   => $authToken
                        ],
                    ]);
                    $transactionData = json_decode($response->getBody());
                    return $this->sendResponse($transactionData,'Lista de tarjetas');
                }else{
                    $message = 'Datos del usuario no encontrados';
                    return $this->sendError($message ,'Opps.. algo ha sucedido, contactese con el administrador del sistema', 404);
                }
            }else{
                $message = 'Datos del usuario no encontrados';
                return $this->sendError($message ,'Opps.. algo ha sucedido, contactese con el administrador del sistema', 404);
            }
        } catch (\Exception $e) {
            return $this->sendError($e , $e->getMessage(), 400);
        }
    }

    public function deleteCards(Request $request){
        try {
            if($request->user_id && $request->card){
                //VALIDAR QUE EL USUARIO ESTE REGISTRADO
                $validateUser = User::where('id', $request->user_id)->first();
                if($validateUser){
                    $refundUrl = BemteUtilities::getUrl() . 'card/delete';
                    $client = new Client();
                    $authToken = BemteUtilities::getAuthToken();
                    $transactionData = [
                        "card" => [
                            "token" => $request->card['token']
                        ],
                        "user" => [
                            "id" => $validateUser->id
                        ],
                    ];
                    $response = $client->post($refundUrl, [
                        'headers' => ['Content-Type' => 'application/json', 'Auth-Token' => $authToken],
                        'body' => json_encode($transactionData)
                    ]);
                    //MENSAJE DE RESPUESTA
                    $transactionData = json_decode($response->getBody());
                    return $this->sendResponse($transactionData,'Eliminado correctamente');
                }else{
                    $message = 'Datos del usuario no encontrados';
                    return $this->sendError($message ,'Opps.. algo ha sucedido, contactese con el administrador del sistema', 404);
                }
            }else{
                $message = 'No existen las variables necesarias para ejecutar el proceso.';
                return $this->sendError($message ,'Opps.. algo ha sucedido, contactese con el administrador del sistema', 404);
            }
        } catch (\Exception $e) {
            return $this->sendError($e , $e->getMessage(), 400);
        }
    }

    public function saveBuy(Request $request){
        try{
            if($request->user_id && $request->tokenCard && $request->dataBilling && $request->dataSend && $request->method && $request->products && $request->total && $request->subtotal && $request->iva ){
                //VALIDAR QUE EL USUARIO ESTE REGISTRADO
                $validateUser = User::where('id', $request->user_id)->first();
                if($validateUser){
                    $description = $request->comentary;
                    $dataArray = $this->getProductsData($request->products);
                    $resultDescription = $dataArray[1];
                    $idProducts = $dataArray[0];
                    if($request->method === "tarjeta"){
                        //Transactional Paymentez
                        $transactionData = null;
                        //PAGO POR TARJETA
                        $date = new DateTime();
                        $unix_timestamp = $date->getTimestamp();
                        $debitData = [
                            'order' => [
                                'amount' => (float)$request->total,
                                'description' => $resultDescription,
                                'dev_reference' => $request->dataBilling['name'] . '-' . $unix_timestamp,
                                'vat' => (float)$request->iva,
                                'installments' => 1,
                                'tax_percentage' => 12, 
                                'taxable_amount' =>  (float)$request->subtotal
                            ],
                            "card" => ["token" => $request->tokenCard],
                            "user" => [
                                "id" => $request->user_id,
                                "email" => $validateUser->email
                            ]
                        ];
                        $debitUrl = BemteUtilities::getUrl() . 'transaction/debit';
                        try {
                            $client = new Client();
                            $authToken = BemteUtilities::getAuthToken();
                            $response = $client->post((string)$debitUrl, [
                                'headers' => ['Content-Type' => 'application/json', 'Auth-Token' => $authToken],
                                'body' => json_encode($debitData),
                            ]);
                        } catch (BadResponseException $e) {
                            $message = 'Error con el medio de pago';
                            return $this->sendError($message.''.$e ,'Opps.. algo ha sucedido, contactese con el administrador del sistema', 404);
                        }
                        $transactionData = json_decode($response->getBody());
                        if (!$transactionData) {
                            $message = 'Error al realizar la transacción';
                            return $this->sendError($message ,'Opps.. algo ha sucedido, contactese con el administrador del sistema', 404);
                        }
                        if (strcmp($transactionData->transaction->status, 'success') != 0) {
                            if (strcmp($transactionData->transaction->status, 'pending') == 0) {
                                $message = 'Error '.$this->estadosString[$transactionData->transaction->status_detail].'-'. $transactionData->transaction;
                                return $this->sendError($message ,'Opps.. algo ha sucedido, contactese con el administrador del sistema', 404);
                            }
                            $message = 'El pago ha sido rechazado';
                            return $this->sendError($message ,'Opps.. algo ha sucedido, contactese con el administrador del sistema', 404);
                        }

                        //CREAR EL PAGO Y ELIMINAR LOS PRODUCTOS
                        // CREAR LA COMPRAR
                        $buy =  Buy::create([
                                    'user_id' => $validateUser->id,
                                    'sendData' => json_encode($request->dataSend),
                                    'billingData' => json_encode($request->dataBilling),
                                    'transaction' => json_encode($transactionData->transaction),
                                    'card' => json_encode($transactionData->card),
                                    'products' => json_encode($idProducts),
                                    'subtotal' => $request->subtotal,
                                    'send' => 0,
                                    'iva'  => $request->iva ,
                                    'total'  => $request->total,
                                    'description' => $description,
                                    'status' => 7,
                                    'method' => $request->method,
                                    'productsArray' => json_encode($request->products),
                                ]);
                        if($buy){
                            //ELIMINAR PRODUCTOS DE COMPRA
                            // $deleteProductsCar = Cart::whereIn('product_id', $idProducts)->where('user_id', $validateUser->id)->delete();
                            // if($deleteProductsCar > 0){
                                //ENVIAR UN CORREO AL USUARIO CON LA COMPRA
                                Mail::to($validateUser->email)->send(new Notificacion($validateUser->name, 
                                        'Su compra fue realizada con éxito ',
                                        '', '', env('EMPRESA')));
                                //RETORNAR MENSAJE DE EXITOSO
                                return $this->sendResponse('Success','Compra realizada correctamente');
                            // }else{
                            //     // APLICAR REMBOLSO
                            // }
                        }else{
                            // APLICAR REMBOLSO
                        }
                    }/*else{
                        
                        //PAGO EN TRANSFERENCIA
                        if ($request->transferencePath && $request->transferencePath !== null) {
                            //PAGO POR TRANSFERENCIA
                            $base64_str = substr($request->transferencePath, strpos($request->transferencePath, ",") + 1);
                            $image = base64_decode($base64_str);
                            if ($image) {
                                $fileName = 'v_' . $validateUser->name . '_' . time() . '.jpg';
                                $url = "/transference/";
                                $storagePath = $url . $fileName;
                                $img = Image::make($image);
                                $img->resize(500, null, function ($e) {
                                    $e->aspectRatio();
                                });
                                $img->save(public_path() . $storagePath, 80);
                            }
                        }
                        // CREAR LA COMPRAR
                        $buy =  Buy::create([
                                    'user_id' => $validateUser->id,
                                    'sendData' => json_encode($request->dataSend),
                                    'billingData' => json_encode($request->dataBilling),
                                    'transaction' => null,
                                    'card' => null,
                                    'products' => json_encode($idProducts),
                                    'subtotal' => $request->subtotal,
                                    'voucher' =>  $storagePath,
                                    'send' => 0,
                                    'iva'  => $request->iva ,
                                    'total'  => $request->total,
                                    'description' => $description,
                                    'status' => 5,
                                    'method' => $request->method,
                                    'productsArray' => json_encode($request->products),
                                ]);
                        if($buy){
                            //ELIMINAR PRODUCTOS DE COMPRA
                            $deleteProductsCar = Cart::whereIn('product_id', $idProducts)->where('user_id', $validateUser->id)->delete();
                            if($deleteProductsCar > 0){
                                //ENVIAR UN CORREO AL USUARIO CON LA COMPRA
                                Mail::to($validateUser->email)->send(new Notificacion($validateUser->name, 
                                        'Su compra fue realizada con éxito ',
                                        '', '', env('EMPRESA')));
                                //RETORNAR MENSAJE DE EXITOSO
                                return $this->sendResponse('Success','Compra realizada correctamente. Los agricultores de esta orden agradecen tu pedido! <br/> Recibirás tus alimentos frescos dentro de las siguientes 24 horas.');
                            }else{
                                $message = 'Error en almacenar los datos de pago';
                                return $this->sendError($message ,'Opps.. algo ha sucedido, contactese con el administrador del sistema', 404);
                            }
                        }else{
                            $message = 'Error en almacenar los datos de pago';
                            return $this->sendError($message ,'Opps.. algo ha sucedido, contactese con el administrador del sistema', 404);
                        }
                    }*/
                }else{
                    $message = 'Datos del usuario no encontrados';
                    return $this->sendError($message ,'Opps.. algo ha sucedido, contactese con el administrador del sistema', 404);
                }
            }else{
                $message = 'No existen las variables necesarias para ejecutar el proceso.';
                return $this->sendError($message ,'Opps.. algo ha sucedido, contactese con el administrador del sistema', 404);
            }
        } catch (Exception $e) {
            return $this->sendError($e , $e->getMessage(), 400);
        }
    }
    
    public function getHistoryBuy(Request $request){
        try{
            if($request->user_id){
                //VALIDAR QUE EL USUARIO ESTE REGISTRADO
                $validateUser = User::where('id', $request->user_id)->first();
                if($validateUser){
                    $data = [];
                    $buy = Buy::with('user_app')->where(['user_id' => $validateUser->id])->orderBy('created_at', 'desc')->paginate(10);
                    if(count($buy) > 0){
                        //ARMAR OBJETO
                        foreach($buy as $buy){
                            $user = json_decode($buy->user_app);
                            $statusData = json_decode($buy->status_buy);
                            $productsID = json_decode($buy->products);
                            $productsBuy = json_decode($buy->productsArray);
                            $dataSend = json_decode($buy->sendData);
                            $dataBilling = json_decode($buy->billingData);
                            $idStatus = $statusData[0]->id;
                            //CANTIDAD TOTAL DE PRODUCTOS
                            $totalProduct = count($productsBuy);
                            //DATE
                            $month = array("Enero","Febrero","Marzo","Abril","Mayo","Junio","Julio","Agosto","Septiembre","Octubre","Noviembre","Diciembre");
                            $date = date_format($buy->created_at, 'Y-m-d');
                            $mes = $month[date_format($buy->created_at,'m')-1];
                            $ano = date_format($buy->created_at,'Y');
                            $day = date_format($buy->created_at,'d');
                            $hour =  date_format($buy->created_at,'H:i');
                            $unifiqueDate = ucfirst($day.' '.$mes .' '. $ano. ' - '.$hour); 
                            //ENCRIPTAR ID COMPRA
                            $idBuy = base64_encode($user[0]->identification.';'.$buy->id).$buy->id;
                            $dataProduct = [];
                            //OBTENER PRODUCTOS DATOS
                            $products =  AlumnoCompra::select(
                                                'user.name as name','alumno_compra.created_at', 'alumno_compra.combo', 
                                                'alumno_compra.valor', 'alumno_compra.horas', 'alumno_compra.estado'
                                            
                                            )
                                            ->join('user', 'alumno_compra.user_id', 'user.id')
                                            ->get();
                            if (count($products)) {
                                $dataProduct = $this->getProduct($products, $validateUser->id);
                            }
                            //VALIDATE STATUS COLOR
                            $isColorRojo = 0;
                            $isColorTomate = 0;
                            $isColorVerde = 0;
                            //SUCCESS
                            if($idStatus === 7 || $idStatus === 3 || $idStatus === 4){
                                $isColorVerde = 1;
                            }
                            //ADVERTENCE
                            if($idStatus === 2 || $idStatus === 1 || $idStatus === 5){
                                $isColorTomate = 1;
                            }
                            //ERROR
                            if($idStatus === 6){
                                $isColorRojo = 1;
                            }
                            $data[] = [
                                'key' => (string)$buy->id,
                                'idBuy' => $idBuy,
                                'user_id' => $buy->user_id,
                                'products' => $dataProduct,
                                'dataSend' => $dataSend,
                                'dataBilling' => $dataBilling,
                                'method' => $buy->method,
                                'pathTransference' => $buy->voucher === null ? '' : asset($buy->voucher), 
                                'statusId' => $idStatus,
                                'status' => $statusData[0]->name,
                                'productBuy' => $productsBuy,
                                'description' => $buy->description,
                                'total' => number_format($buy->total,2),
                                'subtotal' => number_format($buy->subtotal,2),
                                'date' => $unifiqueDate,
                                'isColorVerde' => $isColorVerde,
                                'isColorTomate' => $isColorTomate,
                                'isColorRojo' => $isColorRojo,
                                'totalDetailProduct' => $totalProduct
                            ];
                        }
                    }
                    if(count($data)<= 0){
                        return $this->sendResponse($data ,'Compras no encontradas');
                    }
                    return $this->sendResponse($data, 'Compras encontradas.');
                }else{
                    $message = 'Datos del usuario no encontrados';
                    return $this->sendError($message ,'Opps.. algo ha sucedido, contactese con el administrador del sistema', 404);
                }
            }else{
                $message = 'No existen las variables necesarias para ejecutar el proceso.';
                return $this->sendError($message ,'Opps.. algo ha sucedido, contactese con el administrador del sistema', 404);
            }
        } catch (Exception $e) {
            return $this->sendError($e , $e->getMessage(), 400);
        }
    }
    //REPETIR PRODUCTOS
    
    public function repeatBuy(Request $request){
        try {
            if($request->user_id || $request->products){
                $products = $request->products;
                //VALIDAR QUE EL USUARIO ESTE REGISTRADO
                $validateUser = User::where('id', $request->user_id)->first();
                if($validateUser){
                    $activeRepeatBuy = false;
                    if (count($products) > 0) {      
                        foreach($products as $products){
                            $productSearch =  AlumnoCompra::Find((int)$products['key']);
                            if($productSearch){
                                $activeRepeatBuy = true;
                                //VERIFICAR SI NO EXISTE EN EL CARRITO
                                $validateProductCart =  Cart::where(['product_id' => (int)$productSearch->id, 'user_id' => $validateUser->id])->first();
                                if($validateProductCart){
                                    $validateProductCart->cant = (int)$validateProductCart->cant + (int) $products['cant'];
                                    $validateProductCart->save();
                                }else{
                                    Cart::create([
                                        'product_id' => (int) $productSearch->id,
                                        'user_id' => $validateUser->id,
                                        'status_cart' => 1,
                                        'cant' => (int) $products['cant']
                                    ]);
                                }
                            }
                        }
                    }
                    if(!$activeRepeatBuy){
                        return $this->sendResponse('error', 'No existe productos para repetir compra');
                    }
                    return $this->sendResponse('success', 'Agregado producto al carrito.');
                }else{
                    $message = 'Datos del usuario no encontrados';
                    return $this->sendError($message ,'Opps.. algo ha sucedido, contactese con el administrador del sistema', 404);
                }
            }else{
                $message = 'Opps.. no existen las variables necesarias para consultar la información';
                return $this->sendError($message ,'Opps.. algo ha sucedido, contactese con el administrador del sistema', 404);
            }
        } catch (\Exception $e) {
            return $this->sendError($e , $e->getMessage(), 400);
        }
    }
    
    private function getProductsData($items){
        $arrProductosIds = [];
        $description = '';
        for ($i = 0; $i < count($items); $i++) {
            $arrProductosIds [] = $items[$i]['key'];
            $description = $description . '-' . $items[$i]['name'];
        }
        $resultDescription = substr($description, 0, 246);
        if (strlen($description) >= 246) {
            $resultDescription = $resultDesc . '...';
        }
        return array($arrProductosIds, $resultDescription);
    }
}
