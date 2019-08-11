<?php

namespace App\Http\Controllers;

use App\User;
use App\Alumno;
use App\Profesore;
use App\Ciudad;
use Illuminate\Http\Request;
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
                return response()->json(['success' => 'Cuenta desactivada correctamente' ], 200);
            }
        }
        return response()->json(['error' => 'Ocurrió un error al desactivar su cuenta.'], 401);
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
            return response()->json(['error' => 'El tipo de usuario enviado no es válido'], 401);
        }
        $ciudad = Ciudad::where('ciudad', '=', $request['ciudad'] )->first();
        if (!$ciudad)
        {
            return response()->json(['error' => 'La ciudad enviada no es válida'], 401);
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
                    return response()->json([ 'exist' => 'El email ya pertenece a otro usuario!'], 401);
                }
                $data['email'] = $request['email'];
            }
            if ( isset($request['password']) )
            {
                $data['password'] = bcrypt($request['password']);
            }

            $data['name'] = $request['nombre'].' '.$request['apellido'];
            $avatar = $user['avatar'];
            $token = $user['token'];
            $sistema = $user['sistema'];
            if ( isset($request['avatar']) )
            {
                $data['avatar'] = 'users/'.$request['avatar'];
                $avatar = $data['avatar'];
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
                        $alumno = Alumno::where('user_id', $id_usuario)->select('*')->first();   
                        $alumno['tipo'] = 'Alumno';
                        $alumno['avatar'] = $avatar; 
                        $alumno['token'] = $token; 
                        $alumno['sistema'] = $sistema;  
                        return response()->json(['success' => 'Datos actualizados correctamente', 'profile' => $alumno ], 200);
                    }
                    else
                    {
                        return response()->json(['error' => 'Ocurrió un error al actualizar.'], 401);
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
                    $actualizado = Profesore::where('user_id', $id_usuario )->update( $dataUser );
                    if ($actualizado)
                    {
                        $profesor = Profesore::where('user_id', $id_usuario)->select('*')->first();
                        $profesor['tipo'] = 'Profesor';
                        $profesor['avatar'] = $avatar;
                        $profesor['token'] = $token; 
                        $profesor['sistema'] = $sistema;  
                        return response()->json(['success' => 'Datos actualizados correctamente', 'profile' => $profesor ], 200);
                    }
                    else
                    {
                        return response()->json(['error' => 'Ocurrió un error al actualizar.'], 401);
                    }
                }
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
                return response()->json(['error' => '¡Su cuenta no está activa! Por favor comunicarse con atención al cliente.'], 401);
            }
        }
        else
        {
            return response()->json(['error' => 'Aún no se ha registrado.'], 401);
        }

        if (Hash::check($request['password'], $user['password'])) 
        {
            $avatar = $user['avatar'];
            $token = $user['token'];
            $sistema = $user['sistema'];
            if ($user['tipo'] == 'Alumno')
            {
                $user = Alumno::where('user_id', $user['id'])->select('*')->first();
                $user['tipo'] = 'Alumno';
                $user['avatar'] = $avatar;
                $user['token'] = $token; 
                $user['sistema'] = $sistema;  
            }
            else if ($user['tipo'] == 'Profesor')
            {
                $user = Profesore::where('user_id', $user['id'])->select('*')->first();
                $user['tipo'] = 'Profesor';
                $user['avatar'] = $avatar;
                $user['token'] = $token; 
                $user['sistema'] = $sistema;  
            }
            else
            {
                return response()->json(['error' => 'Usuario no Autorizado en App'], 401);
            }
            return response()->json(['success' => 'Login OK', 'profile' => $user], 200);
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
            $request['confirmation_code'] = str_random(30);
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
            return response()->json([ 'success' => 'Correo enviado correctamente, revise su bandeja de entrada'], 200);
        }
        else
        {
            return response()->json([ 'error' => 'El correo no se encuentra registrado'], 401);
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
            return response()->json([ 'success' => 'Contraseña actualizada con éxito'], 200);
        }
        else
        {
            return response()->json([ 'exist' => 'El correo no se encuentra registrado'], 401);
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
                return response()->json(['error' => 'El tipo de usuario enviado no es válido'], 401);
            }
            if ($request['tipo'] == 'Profesor')
            {
                if (!isset($request['clases']))
                {
                    return response()->json(['error' => 'Indique la Opción Clases'], 401);
                }   
                if ($request['clases'] != "0" && $request['clases'] != "1")
                {
                    return response()->json(['error' => 'Opción Clases incorrecta'], 401);
                }
                if (!isset($request['tareas']))
                {
                    return response()->json(['error' => 'Indique la Opción Tareas'], 401);
                }   
                if ($request['tareas'] != "0" && $request['tareas'] != "1")
                {
                    return response()->json(['error' => 'Opción Tareas incorrecta'], 401);
                }   
            }
            $ciudad = Ciudad::where('ciudad', '=', $request['ciudad'] )->first();
            if (!$ciudad)
            {
                return response()->json(['error' => 'La ciudad enviada no es válida'], 401);
            }
            $cedula = isset($request['cedula']) ? trim($request['cedula']) : NULL;
            if (strlen($cedula) != 10 && strlen($cedula) != 0)
            {
                return response()->json(['error' => 'Cédula inválida'], 401);
            }

            $avatar = isset($request['avatar']) ? 'users/'.$request['avatar'] : NULL;
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
                        return response()->json(['success'=> 'Su cuenta ha sido creada correctamente'], 200);
                    }
                }
                else
                {
                    $hojaVida = isset($request['hojaVida']) ? $request['hojaVida'] : NULL;
                    $titulo = isset($request['titulo']) ? $request['titulo'] : NULL;
                    $cuenta = isset($request['cuenta']) ? $request['cuenta'] : NULL;
                    $banco = isset($request['banco']) ? $request['banco'] : NULL;
                    $banco = isset($request['tipoCuenta']) ? $request['tipoCuenta'] : NULL;
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
                        'tipo_cuenta' => $tipo_cuenta
                    ]);
                    if($profesor)
                    {
                        return response()->json(['success'=> 'Su cuenta ha sido creada correctamente. Por favor espera que validemos su información'], 200);
                    }
                }
            }
            else
            {
                return response()->json(['error' => 'Lo sentimos, ocurrió un error al registrar!'], 401);
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
            return response()->json(['error' => 'No se especificaron datos para actualizar'], 401);
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
                return response()->json(['success' => 'Datos actualizados correctamente'], 200);
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


    public function devuelveUsuario()
    {
        if( \Request::get('user_id') )
        {
            $search = \Request::get('user_id');
            $user = User::where('id', $search)->select('*')->first();
            if($user)
            {
                $avatar = $user['avatar'];
                $token = $user['token'];
                $sistema = $user['sistema'];
                if ($user['tipo'] == 'Alumno')
                {
                    $user = Alumno::where('user_id', $user['id'])->select('*')->first();
                    $user['tipo'] = 'Alumno';
                    $user['avatar'] = $avatar;
                    $user['token'] = $token; 
                    $user['sistema'] = $sistema;  
                }
                else if ($user['tipo'] == 'Profesor')
                {
                    $user = Profesore::where('user_id', $user['id'])->select('*')->first();
                    $user['tipo'] = 'Profesor';
                    $user['avatar'] = $avatar;
                    $user['token'] = $token; 
                    $user['sistema'] = $sistema;  
                }
                else
                {
                    return response()->json(['error' => 'Usuario no Autorizado en App'], 401);
                }
                return response()->json(['success' => 'Login OK', 'profile' => $user], 200);
            }
            else
            {
                return response()->json(['error' => 'Usuario no registrado.'], 401);
            }
        }
        else
        {
            return response()->json(['error' => 'Usuario no especificado'], 401);
        }
    }
}