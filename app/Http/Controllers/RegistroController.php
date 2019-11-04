<?php

namespace App\Http\Controllers;

use App\User;
use App\Ciudad;
use App\Alumno;
use App\Profesore;
use App\Materia;
use App\ProfesorMaterium;
use App\Mail\Notificacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Validator;
use Hash;

class RegistroController extends Controller
{
    public function eliminarCuenta(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required',
            'tipo' => 'required'
        ]);
        if ($validator->fails()) 
        {
            return response()->json(['error' => $validator->errors()], 406);
        }
        $id_usuario = $request['user_id'];

        $result = User::where( 'id', $id_usuario )->update(['activo' => false]);
        if ($result)
        {
            if ($request['tipo'] == 'Alumno')
            {
                $result = Alumno::where( 'user_id', $id_usuario)->update(['activo' => false]);
            }
            else
            {
                $result = Profesores::where( 'user_id', $id_usuario)->update(['activo' => false]);
            }
            if ($result)
            {
                return response()->json(['success' => 'Cuenta Desactivada' ], 200);
            }
        }
        return response()->json(['error' => 'Error al desactivar su cuenta'], 401);
    }

    public function actualizarCuenta(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required',
            'email' => 'required|email',
            'nombre' => 'required|min:3|max:50',
            'apellido' => 'required|min:3|max:50',
            'apodo' => 'required|min:3|max:20',
            'ubicacion' => 'required',
            'ciudad' => 'required',
            'tipo' => 'required',
            'celular' => 'required',
            'tipo' => 'required'
        ]);
        if ($validator->fails()) 
        {
            return response()->json(['error' => $validator->errors()], 406);
        }
        if (($request['tipo'] != 'Alumno') && ($request['tipo'] != 'Profesor'))
        {
            return response()->json(['error' => 'Tipo de Usuario inválido'], 401);
        }
        $ciudad = Ciudad::where('ciudad', '=', $request['ciudad'] )->first();
        if (!$ciudad)
        {
            return response()->json(['error' => 'Ciudad inválida'], 401);
        }
        $cedula = isset($request['cedula']) ? trim($request['cedula']) : NULL;
        if (strlen($cedula) != 10 && strlen($cedula) != 0)
        {
            return response()->json(['error' => 'Cédula inválida'], 401);
        }

        $id_usuario = $request['user_id'];
        $user = User::where('id', $id_usuario)->select('*')->first();
        if ($user)
        {
            if ($user['email'] != $request['email'])
            {
                $emailVerified = User::where('email', '=', $request['email'] )->first();
                if ($emailVerified !== null) 
                {
                    return response()->json([ 'exist' => 'Email pertenece a otro usuario!'], 401);
                }
                $data['email'] = $request['email'];
            }
            $newPassword = isset($request['newPassword']) ? trim($request['newPassword']) : '';
            if (strlen($newPassword) > 0)
            {
                if (!Hash::check($request['oldPassword'], $user['password'])) 
                    return response()->json([ 'exist' => 'Credenciales Incorrectas!'], 401);
                $data['password'] = bcrypt($newPassword);
            }

            $data['name'] = $request['nombre'].' '.$request['apellido'];
            $token = $user['token'];
            $sistema = $user['sistema'];
            if (isset($request['avatar']))
            {
                $archivo = 'uploads'.'/'.$id_usuario.'/'.trim($request['avatar']);
                if ($user['avatar'] != $archivo && strpos($archivo, 'http') === FALSE)
                    $data['avatar'] = $archivo;
            }
            
            $actualizado = User::where('id', $id_usuario )->update( $data );
            if( $actualizado )
            {
                $dataUser['nombres'] = $request['nombre'];
                $dataUser['apellidos'] = $request['apellido'];
                $dataUser['apodo'] = $request['apodo'];
                $dataUser['correo'] = $request['email'];
                $dataUser['ubicacion'] = $request['ubicacion'];
                $dataUser['ciudad'] = $request['ciudad'];
                $dataUser['celular'] = $request['celular'];
                if ($request['tipo'] == 'Alumno')
                {
                    $actualizado = Alumno::where('user_id', $id_usuario )->update( $dataUser );               
                    if ($actualizado)
                    {
                        $userDev = $this->datosUser($id_usuario);
                        return response()->json(['success' => 'Datos Actualizados!', 'profile' => $userDev], 200);
                    }
                    else
                    {
                        return response()->json(['error' => 'Ocurrió un error al actualizar'], 401);
                    }
                }
                else
                {
                    $dataUser['cedula'] = $cedula;
                    if ( isset($request['hojaVida']) )
                    {
                        $dataUser['hoja_vida'] = $request['hojaVida'];
                    }
                    if ( isset($request['titulo']) )
                    {
                        $dataUser['titulo'] = $request['titulo'];
                    }
                    if ( isset($request['cuenta']) )
                    {
                        $dataUser['cuenta'] = $request['cuenta'];
                    }
                    if ( isset($request['banco']) )
                    {
                        $dataUser['banco'] = $request['banco'];
                    }
                    if ( isset($request['tipoCuenta']) )
                    {
                        $dataUser['tipo_cuenta'] = $request['tipoCuenta'];
                    }
                    if ( isset($request['descripcion']) )
                    {
                        $dataUser['descripcion'] = $request['descripcion'];
                    }
                    if ( isset($request['fecha_nacimiento']) )
                    {
                        $dataUser['fecha_nacimiento'] = $request['fecha_nacimiento'];
                    }
                    if ( isset($request['genero']) )
                    {
                        $dataUser['genero'] = $request['genero'];
                    }
                    if ( isset($request['clases']) )
                    {
                        $dataUser['clases'] = $request['clases'];
                    }
                    if ( isset($request['tareas']) )
                    {
                        $dataUser['tareas'] = $request['tareas'];
                    }
                    $actualizado = Profesore::where('user_id', $id_usuario )->update( $dataUser );

                    $materia1 = Materia::where('nombre', $request['materia1'])->first();
                    $materia2 = Materia::where('nombre', $request['materia2'])->first();
                    $materia3 = Materia::where('nombre', $request['materia3'])->first();
                    $materia4 = Materia::where('nombre', $request['materia4'])->first();
                    $materia5 = Materia::where('nombre', $request['materia5'])->first();
                    $materias = ProfesorMaterium::where('user_id', $user->id)->get();
                    foreach($materias as $mat)
                    {
                        if (($materia1 != null && $mat->materia == $materia1->nombre) 
                            || ($materia2 != null && $mat->materia == $materia2->nombre) 
                            || ($materia3 != null && $mat->materia == $materia3->nombre)
                            || ($materia4 != null && $mat->materia == $materia4->nombre) 
                            || ($materia5 != null && $mat->materia == $materia5->nombre))
                        {
                            if (!$mat->activa)
                            {
                                $dataMat['activa'] = true;
                                ProfesorMaterium::where('id', $mat->id )->update( $dataMat );
                            }
                        }
                        else
                        {
                            $dataMat['activa'] = false;
                            ProfesorMaterium::where('id', $mat->id )->update( $dataMat );
                        }
                    }
                    if ($materia1 != null && $materias->where('materia', $materia1->nombre)->count()== 0)
                        ProfesorMaterium::create([
                                'user_id' => $user->id,
                                'materia' => $materia1->nombre,
                                'activa' => true
                            ]);
                    if ($materia2 != null && $materias->where('materia', $materia2->nombre)->count()== 0)
                        ProfesorMaterium::create([
                                'user_id' => $user->id,
                                'materia' => $materia2->nombre,
                                'activa' => true
                            ]);
                    if ($materia3 != null && $materias->where('materia', $materia3->nombre)->count()== 0)
                        ProfesorMaterium::create([
                                'user_id' => $user->id,
                                'materia' => $materia3->nombre,
                                'activa' => true
                            ]);
                    if ($materia4 != null && $materias->where('materia', $materia4->nombre)->count()== 0)
                        ProfesorMaterium::create([
                                'user_id' => $user->id,
                                'materia' => $materia4->nombre,
                                'activa' => true
                            ]);
                    if ($materia5 != null && $materias->where('materia', $materia5->nombre)->count()== 0)
                        ProfesorMaterium::create([
                                'user_id' => $user->id,
                                'materia' => $materia5->nombre,
                                'activa' => true
                            ]);
                    if ($actualizado)
                    {
                        $userDev = $this->datosUser($id_usuario);
                        return response()->json(['success' => 'Datos Actualizados!', 'profile' => $userDev], 200);
                    }
                    else
                    {
                        return response()->json(['error' => 'Ocurrió un error al actualizar'], 401);
                    }
                }
            }
            else
            {
                return response()->json(['error' => 'Ocurrió un error al actualizar'], 401);
            }
        } 
        else
        {
            return response()->json(['error' => 'No se encontró el Usuario'], 401);
        }
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|min:6|max:20'
        ]);
        if ($validator->fails()) 
        {
            return response()->json(['error' => $validator->errors()], 406);
        }

        $user = User::where('email', $request['email'])->select('*')->first();
        if($user)
        {
            if($user['activo'] == false)
            {
                return response()->json(['error' => '¡Cuenta inactiva!'], 401);
            }
        }
        else
        {
            return response()->json(['error' => 'Aún no se ha registrado!'], 401);
        }

        if (Hash::check($request['password'], $user['password'])) 
        {
            $userDev = $this->datosUser($user->id);
            return response()->json(['success' => 'Login OK', 'profile' => $userDev], 200);
        }
        else
        {
            return response()->json(['error' => 'Credenciales Incorrectas'], 401);
        }
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
        
        if ($email) 
        {
            // generate random code to verify
            $request['confirmation_code'] = str_random(10);
            $mytime = date("Y-m-d H:i:s");  

            DB::insert('insert into password_resets (email, token,fecha) values (?, ?, ?)', [$request['email'], $request['confirmation_code'],$mytime]);

            $this->actual_email = $request['email'];
            $variables = ['link_reset' => url("/api/reset/")."/".$request['confirmation_code']."/". $this->actual_email , 'message' => 'message','empresa_name'=>'BEMTE' ];
            //Vista Emails
            Mail::send('emails.reset', $variables, function ($message) 
            {
                $message->from('etg@boxqos.ec'); //revisar correo de envío
                $message->to( $this->actual_email );
                $message->subject('Código de verificación');
            });
            return response()->json([ 'success' => 'Correo Enviado'], 200);
        }
        else
        {
            return response()->json([ 'error' => 'Correo no registrado'], 401);
        }
    }

    public function resetPassApp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required'
        ]);
        if ($validator->fails()) 
        {
            return response()->json(['error' => $validator->errors()], 406);
        }

        $email = User::where('email', '=', $request['email'] )->first();
        if ($email) 
        {
            $pass= str_random(10);
            $data['password'] = bcrypt($pass);
            $act = User::where('id', $email->id )->update($data);
            if(!$act )
            {
                return response()->json(['error' => 'Error al cambiar contraseña'], 401);
            }
            try 
            {
                Mail::to($request['email'])->send(new Notificacion($email->name, 
                        'Su nueva contraseña es:', '',  $pass, env('EMPRESA'), true));
            }
            catch (Exception $e) 
            {
                return response()->json(
                            ['error' => 'No se pudo enviar el correo',
                            'detalle' => $e->getMessage()], 401);
            }
            return response()->json([ 'success' => 'Correo Enviado'], 200);
        }
        else
        {
            return response()->json([ 'error' => 'Correo no registrado'], 401);
        }
    }

    public function validar($confirmation_code,$email)
    {
        $codigo = DB::table('password_resets')->where('token', '=', $confirmation_code )->first();
        if($codigo)
        {
            return view('reset',['token' => $confirmation_code, 'email'=>$email]);
        }
        else
        {
            return view('reset_err');
        }
    }
    
    public function actualizarPW(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required',
            'password' => 'required|min:6|max:20',
            'token'=>'required'
        ]);
        if ($validator->fails())
        {
            return response()->json(['error' => $validator->errors()], 406);
        }

        $email = User::where('email', '=', $request['email'] )->first();
        if ($email)
        {
            $request['password']=bcrypt( $request['password']);
            DB::table('users')->where('email', $request['email'])->update(['password' => $request['password']]);
            DB::table('password_resets')->where('token', $request['token'])->delete();
            return response()->json([ 'success' => 'Contraseña Actualizada!'], 200);
        }
        else
        {
            return response()->json([ 'exist' => 'Correo no registrado'], 401);
        }        
    }
    
    public function registro(Request $request)
    {
        if ($request) 
        {
            $validator = Validator::make($request->all(), [
                'nombre' => 'required|min:3|max:50',
                'apellido' => 'required|min:3|max:50',
                'apodo' => 'required|min:3|max:20',
                'email' => 'required|email',
                'password' => 'required|min:6|max:20',
                'ubicacion' => 'required',
                'ciudad' => 'required',
                'tipo' => 'required',
                'celular' => 'required'
            ]);
            if ($validator->fails()) 
            {
                return response()->json(['error' => $validator->errors()], 406);
            }
            
            $user = User::where('email', '=', $request['email'] )->first();
            if ($user != null) 
            {
                return response()->json([ 'exist' => 'El usuario ya existe!'], 401);
            }
            if (($request['tipo'] != 'Alumno') && ($request['tipo'] != 'Profesor'))
            {
                return response()->json(['error' => 'Tipo de usuario inválido'], 401);
            }
            if ($request['tipo'] == 'Profesor')
            {
                if (!isset($request['clases']))
                {
                    return response()->json(['error' => 'Indique opción Clases'], 401);
                }   
                if ($request['clases'] != "0" && $request['clases'] != "1")
                {
                    return response()->json(['error' => 'Opción Clases incorrecta'], 401);
                }
                if (!isset($request['tareas']))
                {
                    return response()->json(['error' => 'Indique opción Tareas'], 401);
                }   
                if ($request['tareas'] != "0" && $request['tareas'] != "1")
                {
                    return response()->json(['error' => 'Opción Tareas incorrecta'], 401);
                }   
            }
            $ciudad = Ciudad::where('ciudad', '=', $request['ciudad'] )->first();
            if (!$ciudad)
            {
                return response()->json(['error' => 'Ciudad inválida'], 401);
            }
            $cedula = isset($request['cedula']) ? trim($request['cedula']) : NULL;
            if (strlen($cedula) != 10 && strlen($cedula) != 0)
            {
                return response()->json(['error' => 'Cédula inválida'], 401);
            }

            $avatar = isset($request['avatar']) ? 'uploads'.'/'.$id_usuario.'/'.trim($request['avatar']) : NULL;
            $token = isset($request['token']) ? $request['token'] : NULL;
            $sistema = isset($request['sistema']) ? $request['sistema'] : NULL;
            $user = User::create([
                'role_id' => $request['tipo'] == 'Alumno' ? 2 : 4,
                'name' => $request['nombre'].' '.$request['apellido'],
                'email' => $request['email'],
                'password' => bcrypt($request['password']),
                'created_at' => $request['created_at'],
                'updated_at' => $request['created_at'],
                'tipo' => $request['tipo'],
                'activo' => true,
                'avatar' => $avatar,
                'token' => $token,
                'sistema' => $sistema
            ]);

            if( $user->id)
            {
                if($request['tipo'] == 'Alumno' )
                {
                    $alumno = Alumno::create([
                        'user_id' => $user->id,
                        'celular' => $request['celular'],
                        'correo' => $user->email,
                        'nombres' => $request['nombre'],
                        'apellidos' => $request['apellido'],
                        'apodo' => $request['apodo'],
                        'ubicacion' => $request['ubicacion'],
                        'ciudad' => $request['ciudad'],
                        'ser_profesor' => false,
                        'activo' => true,
                        'created_at' => $request['created_at'],
                        'updated_at' => $request['created_at']
                    ]);
                    if($alumno)
                    {
                        $userDev = $this->datosUser($user->id);
                        return response()->json(['success' => 'Cuenta Creada!', 'profile' => $userDev], 200);
                    }
                }
                else
                {
                    $hojaVida = isset($request['hojaVida']) ? $request['hojaVida'] : NULL;
                    $titulo = isset($request['titulo']) ? $request['titulo'] : NULL;
                    $cuenta = isset($request['cuenta']) ? $request['cuenta'] : NULL;
                    $banco = isset($request['banco']) ? $request['banco'] : NULL;
                    $tipo_cuenta = isset($request['tipoCuenta']) ? $request['tipoCuenta'] : NULL;
                    $descripcion = isset($request['descripcion']) ? $request['descripcion'] : NULL;
                    $fecha_nacimiento = isset($request['fecha_nacimiento']) ? $request['fecha_nacimiento'] : NULL;
                    $genero = isset($request['genero']) ? $request['genero'] : NULL;
                    $profesor = Profesore::create([
                        'user_id' => $user->id,
                        'celular' => $request['celular'],
                        'correo' => $user->email,
                        'nombres' => $request['nombre'],
                        'apellidos' => $request['apellido'],
                        'cedula' => $cedula,
                        'apodo' => $request['apodo'],
                        'ubicacion' => $request['ubicacion'],
                        'ciudad' => $request['ciudad'],
                        'clases' => $request['clases'] == "1" ? true : false,
                        'tareas' => $request['tareas'] == "1" ? true : false,
                        'disponible' => true,
                        'hoja_vida ' => $hojaVida,
                        'titulo ' => $titulo,
                        'activo' => false,
                        'created_at' => $request['created_at'],
                        'updated_at' => $request['created_at'],
                        'cuenta' => $cuenta,
                        'banco' => $banco,
                        'tipo_cuenta' => $tipo_cuenta,
                        'descripcion' => $descripcion,
                        'fecha_nacimiento' => $fecha_nacimiento,
                        'genero' => $genero
                    ]);
                    if($profesor)
                    {
                        $materia1 = Materia::where('nombre', $request['materia1'])->first();
                        $materia2 = Materia::where('nombre', $request['materia2'])->first();
                        $materia3 = Materia::where('nombre', $request['materia3'])->first();
                        $materia4 = Materia::where('nombre', $request['materia4'])->first();
                        $materia5 = Materia::where('nombre', $request['materia5'])->first();
                        if ($materia1 != null)
                            ProfesorMaterium::create([
                                'user_id' => $user->id,
                                'materia' => $materia1->nombre,
                                'activa' => true
                            ]);
                        if ($materia2 != null)
                            ProfesorMaterium::create([
                                'user_id' => $user->id,
                                'materia' => $materia2->nombre,
                                'activa' => true
                            ]);
                        if ($materia3 != null)
                            ProfesorMaterium::create([
                                'user_id' => $user->id,
                                'materia' => $materia3->nombre,
                                'activa' => true
                            ]);
                        if ($materia4 != null)
                            ProfesorMaterium::create([
                                'user_id' => $user->id,
                                'materia' => $materia4->nombre,
                                'activa' => true
                            ]);
                        if ($materia5 != null)
                            ProfesorMaterium::create([
                                'user_id' => $user->id,
                                'materia' => $materia5->nombre,
                                'activa' => true
                            ]);

                        $userDev = $this->datosUser($user->id);
                        return response()->json(['success' => 'Cuenta Creada!', 'profile' => $userDev], 200);
                    }
                }
            }
            else
            {
                return response()->json(['error' => 'Ocurrió un error al registrar!'], 401);
            }

            // send email
            /*
            Mail::queue('emails.verify', $data, function($message) use ($data) {
                $message->to($data['email'])->subject('Verify your email address');
            });*/
        } 
        else 
        {
            return response()->json(['error' => 'Formulario vacío!'], 401);
        }
    }

    public function actualizarToken(Request $request)
    {
        if (!isset($request['token']) && !isset($request['sistema']))
        {
            return response()->json(['error' => 'Sin datos para actualizar'], 401);
        }
        $id_usuario = $request['user_id'];
        $user = User::where('id', $id_usuario)->select('*')->first();
        if ($user)
        {
            if (isset($request['token']))
            {
                $data['token'] = $request['token'];
            }
            if (isset($request['sistema']))
            {
                $data['sistema'] = $request['sistema'];
            }

            $actualizado = User::where('id', $id_usuario )->update( $data );
            if( $actualizado )
            {
                return response()->json(['success' => 'Token Actualizado!'], 200);
            }
            else
            {
                return response()->json(['error' => 'Ocurrió un error al actualizar'], 401);
            }
        } 
        else
        {
            return response()->json(['error' => 'No se encontró Usuario'], 401);
        }
    }

    public function devuelveUsuario()
    {
        if( \Request::get('user_id') )
        {
            $search = \Request::get('user_id');
            $user = $this->datosUser($search);
            if($user != null)
                return response()->json(['success' => 'Login OK', 'profile' => $user], 200);
            else
                return response()->json(['error' => 'Usuario no Autorizado'], 401);
        }
        else
        {
            return response()->json(['error' => 'Usuario no especificado'], 401);
        }
    }

    public function datosUser($idUser)
    {
        $user = null;
        $userPro = User::where('id', $idUser)->first();
        if ($userPro != null)
        {
            if ($userPro->tipo == 'Alumno')
            {
                $user = Alumno::join('ciudad', 'ciudad.ciudad', '=', 'alumnos.ciudad')
                    ->where('user_id', $userPro['id'])
                    ->select('user_id', 'celular', 'correo', 'nombres', 'apellidos', 'correo', 'apodo', 
                        'ubicacion', 'alumnos.ciudad', 'ser_profesor', 'activo', 'billetera',
                        'pais', 'codigo')->first();
                    $user['tipo'] = 'Alumno';
                    $user['avatar'] = $userPro->avatar;
                    $user['token'] = $userPro->token; 
                    $user['sistema'] = $userPro->sistema;  
            }
            if ($userPro->tipo == 'Profesor')
            {
                $user = Profesore::join('ciudad', 'ciudad.ciudad', '=', 'profesores.ciudad')
                ->where('user_id', $userPro['id'])
                ->select('user_id', 'celular', 'correo', 'nombres', 'apellidos', 'cedula', 'correo', 
                    'apodo', 'ubicacion', 'profesores.ciudad', 'clases', 'tareas', 'disponible', 'hoja_vida', 
                    'titulo', 'activo', 'cuenta', 'banco', 'tipo_cuenta', 'valor_clase', 'valor_tarea',
                    'pais', 'codigo', 'descripcion', 'fecha_nacimiento', 'genero')->first();
                $user['tipo'] = 'Profesor';
                $user['avatar'] = $userPro->avatar;
                $user['token'] = $userPro->token; 
                $user['sistema'] = $userPro->sistema;
                $materias = ProfesorMaterium::where('user_id', $userPro['id'])->where('activa', true)->get();
                $contador = 1;
                foreach ($materias as $mat)
                {
                    $user['materia'.$contador] = $mat->materia;
                    $contador++;
                }
                while ($contador < 6)
                {
                    $user['materia'.$contador] = '';
                    $contador++;
                }
            }
        }
        return $user;

    }

    public function correoDisponible()
    {
        $search = \Request::get('email');
        $userID = \Request::get('user_id');
        $user = User::where('email', $search)->first();
        $respuesta = ($user != null && $user->id != $userID) ? false : true;
        return response()->json($respuesta, 200);
    }

    public function apodoDisponible()
    {
        $respuesta = true;
        $search = \Request::get('apodo');
        $userID = \Request::get('user_id');
        $profesor = Profesore::where('apodo', $search)->first();
        if ($profesor != null && $profesor->user_id != $userID)
            $respuesta = false;
        if ($profesor == null)
        {
            $alumno = Alumno::where('apodo', $search)->first();
            if ($alumno != null && $alumno->user_id != $userID)
                $respuesta = false;
        }
        return response()->json($respuesta, 200);
    }
}