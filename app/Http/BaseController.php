<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller as Controller;

class BaseController extends Controller
{
    /**
     * success response method.
     *
     * @return \Illuminate\Http\Response
     */
    public function sendResponse($result, $message)
    {
    	$response = [
            'status' => true,
            'data'    => $result,
            'message' => $message,
            'code' =>  200
        ];
        return response()->json($response, 200);
    }

    /**
     * return error response.
     *
     * @return \Illuminate\Http\Response
     */
    public function sendError($error, $errorMessages,  $code = 404){
        $message = "Opps.. algo ha sucedido, contactese con el administrador del sistema";
    	$response = [
            'status' => false,
            'message' => getType($error) === "object" || getType($error) === "array" ? $message : $error,
            'code' =>  $code,
            'errorCode' => $errorMessages 
        ];
        return response()->json($response, $code);
    }

    public function sendValidation($error, $errorMessages = [], $code = 202){
        $response = [
            'status' => false,
            'code'    => $code,
            'message' => $error,
            'errorCode' => $errorMessages,
            'validation' => $errorMessages
        ];
        return response()->json($response, $code);
    }
}
