<?php

namespace App\Http\Controllers;

use App\User;
use App\Alumno;
use App\Profesore;
use App\Profile;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Mail;
use Validator;


class LoginController extends Controller
{
    public function __construct()
    {
        $this->middleware('jwt.auth', ['except' => ['login','resetPassword','validar','actualizar','register']]);
    }

    private $actual_email = "";

    public function index()
    {
        if (Auth::user())
            return response()->json(['success' => 'Token activo'], 200);
        else
            return response()->json(['error' => 'Token no activo'], 403);
    }

    public function getUser(){
        $id_usuario = Auth::user()->id;

        $user = User::where('id', $id_usuario)->select('*')->first();
        return response()->json( $user , 200);
    }


    public function eliminarCuenta(){

        $id_usuario = Auth::user()->id;

        $result = User::where( 'id', $id_usuario  )->update(['activo' => false]);
        
        if( $result ){
            return response()->json(['success' => 'Cuenta desactivada correctamente' ], 200);
        }
        return response()->json(['error' => 'Ocurrió un error al desactivar su cuenta.'], 401);
    }


    public function updateProfile( Request $request ){
        $id_usuario = Auth::user()->id;

        $validator = Validator::make($request->all(), [
            'name' => 'required|min:3|max:50',
            'email' => 'required'
        ]);

        $data['name'] = $request['name'];
        $data['email'] = $request['email'];

        if( $request['avatar'] ){
            $data['avatar'] = 'users/'.$request['avatar'];
        }

        $user = User::where('id', $id_usuario)->select('*')->first();
        
        if($user)
        {
            $actualizado = User::where('id', $id_usuario )->update( $data );
            if( $actualizado )
            {
                return response()->json(['success' => 'Datos actualizados correctamente', 'profile' => $user ], 200);
            }
            else
            {
                return response()->json(['error' => 'Ocurrió un error al actualizar.'], 401);
            }
        } 
        else
        {
            return response()->json(['error' => 'No se encontró el usuario.'], 401);
        }
    }


    public function login(Request $request)
    {
        $tmp= User::where('email', $request['email'])->select('activo')->first();
        if($tmp)
        {
            if($tmp['activo'] == false)
            {
                return response()->json(['error' => '¡Su cuenta no está activa! Por favor comuynicarse con atención al cliente.'], 401);
            }
            /*
            elseif($tmp['status'] == 2){
                return response()->json(['error' => '¡Su cuenta ha sido eliminada! Para poder volver a renovarla deve comunicarse con atención al cliente.'], 401);

            }else{
                $request['status'] = $tmp['status'];
            }
            */
        }else{
            return response()->json(['error' => 'Aún no se ha registrado.'], 401);
        }

        $credentials = $request->only('email', 'password'); //,'status');
        try 
        {
            if (! $token = JWTAuth::attempt($credentials)) 
            {
                return response()->json(['error' => 'Credenciales incorrectas'], 401);
            }
        } catch (JWTException $e) 
        {
            return response()->json(['error' => 'could_not_create_token'], 500);
        }
        
        $user=Auth::user();
        return response()->json(compact('token','user'));
    }

    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required'
        ]);

        if ($validator->fails()) 
        {
            return response()->json(['error' => $validator->errors()], 406);
        }

        $email = User::where('email', '=', $request['email'] )->first();
        // generate random code to verify
        $request['confirmation_code'] = str_random(30);
        $mytime = date("Y-m-d H:i:s");  
        
        if ($email) 
        {
            DB::insert('insert into password_resets (email, token,fecha) values (?, ?, ?)', [$request['email'], $request['confirmation_code'],$mytime]);
            //return  $mytime;
            //return  $request['confirmation_code'];
            //return response()->json([ 'exist' => 'El usuario ya existe!'], 200);

            $this->actual_email = $request['email'];
            $variables = ['link_reset' => url("/api/reset/")."/".$request['confirmation_code']."/". $this->actual_email , 'message' => 'message','empresa_name'=>'BEMTE' ];
            //Vista Emails
            Mail::send('emails.reset', $variables, function ($message) 
            {
                $message->from('etg@boxqos.ec'); //revisar correo de envío
                $message->to( $this->actual_email );
                $message->subject('Código de verificación');
            });
            return response()->json([ 'exist' => 'Correo enviado correctamente, revise su bandeja de entrada'], 200);
        }
        else
        {
            return response()->json([ 'error' => 'El correo no se encuentra registrado'], 406);
        }
    }


    public function validar($confirmation_code,$email)
    {
        //var_dump($email);
        //$codigo = User::where('email', '=', $request['email'] )->first();
        
        $codigo = DB::table('password_resets')->where('token', '=', $confirmation_code )->first();
        //var_dump($codigo);
        if($codigo)
        {
            return view('reset',['token' => $confirmation_code, 'email'=>$email]);
        }else
        {
            return view('reset_err');
        }
    }

    
    public function actualizar(Request $request){
        $validator = Validator::make($request->all(), [
            'email' => 'required',
            'password'=>'required',
            'token'=>'required'
        ]);
   
        //return($request['token']);

        if ($validator->fails())
        {
            return response()->json(['error' => $validator->errors()], 406);
        }

        $request['password']=bcrypt( $request['password']);
        $email = User::where('email', '=', $request['email'] )->first();
        // generate random code to verify
        if ($email)
        {
            DB::table('users')->where('email',$request['email'])->update(['password' => $request['password']]);
            DB::table('password_resets')->where('token',$request['token'])->delete();
            return response()->json([ 'exist' => 'Contraseña actualizada con éxito'], 200);
        }else
        {
            return response()->json([ 'exist' => 'El correo no se encuentra registrado'], 406);
        }        
    }

    
    public function register(Request $request)
    {
        if ($request) {

            $user = User::where('email', '=', $request['email'] )->first();
            if ($user !== null) 
            {
                return response()->json([ 'exist' => 'El usuario ya existe!'], 200);
            }

            $validator = Validator::make($request->all(), [
                'nombre' => 'required|min:3|max:50',
                'apellido' => 'required|min:3|max:70',
                'email' => 'required|email|unique:users,email',
                'password' => 'required|min:6|max:20',
                'tipo' => 'required',
                'celular' => 'required'
            ]);
            if ($validator->fails()) 
            {
                return response()->json(['error' => $validator->errors()], 406);
            }
            if (($request['tipo'] != 'Alumno') && ($request['tipo'] != 'Profesor'))
            {
                return response()->json(['error' => 'El tipo de usuario enviado no es válido'], 406);
            }

            $avatar = $request['avatar'] ? 'users/'.$request['avatar'] : NULL;

            // define password
            $user = User::create([
                'role_id' => 2,
                'name' => $request['nombre'].' '.$request['apellido'],
                'email' => $request['email'],
                'password' => bcrypt($request['clave']),
                'created_at ' => $request['created_at '],
                'updated_at ' => $request['created_at '],
                'tipo' => $request['tipo'],
                'activo' => true,

                'avatar' => $avatar
            ]);

            if( $user->id)
            {
                if($request['tipo'] == 'Alumno' ){

                    $alumno = Alumno::create([
                        'user_id' => $user->id,
                        'celular' => $request['celular'],
                        'correo' => $user->id,
                        'nombres' => $user->id,
                        'apellidos' => $user->id,
                        'apodo' => $user->id,
                        'ubicacion' => $request['marca'],
                        'ciudad' => $request['modelo'],
                        'ser_profesor' => false,
                        'activo' => transliterator_create_from_rules
                    ]);
                    if($alumno)
                    {
                        return response()->json(['ok'=> 'Su cuenta ha sido creada correctamente. Por favor espera que validemos tu información'], 200);
                    }
                
                }else
                {
                    return response()->json(['ok'=> 'Su cuenta ha sido creada correctamente. Por favor espera que validemos tu información'], 200);
                }
            }
            else
            {
                return response()->json(['error' => 'Lo sentimos, ocurrió un error al registrar!'], 401);
            }
                
            //$data = $request->only('name', 'email');

                // set user role id in relationship table
                //DB::insert('insert into users (role_id,name,email,password) values (?, ?, ?,?)', [$request['role_id'],$request['name'],$request['email'],$request['password']]);


            // send email
            /*
            Mail::queue('emails.verify', $data, function($message) use ($data) {
                $message->to($data['email'])->subject('Verify your email address');
            });*/

        } 
        else 
        {
            return response()->json(['error' => 'Form is empty!'], 401);
        }
    }


    public function me()
    {
        return response()->json($this->guard()->user());
    }

    /**
     * Log the user out (Invalidate the token)
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        $this->guard()->logout();

        return response()->json(['message' => 'Successfully logged out']);
    }

    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh()
    {
        return $this->respondWithToken($this->guard()->refresh());
    }

    /**
     * Get the token array structure.
     *
     * @param  string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondWithToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => $this->guard()->factory()->getTTL() * 60
        ]);
    }

    /**
     * Get the guard to be used during authentication.
     *
     * @return \Illuminate\Contracts\Auth\Guard
     */
    public function guard()
    {
        return Auth::guard();
    }
}