<?php

namespace App\Http\Controllers\Voyager;

use Illuminate\Http\Request;
use TCG\Voyager\Facades\Voyager;
use Auth;
use App\User;
use App\Profesore;

class VoyagerUserController extends VoyagerBaseController
{
    public function profile(Request $request)
    {
        return Voyager::view('voyager::profile');
    }

    // POST BR(E)AD
    public function update(Request $request, $id)
    {
        if (app('VoyagerAuth')->user()->getKey() == $id) {
            $request->merge([
                'role_id'                              => app('VoyagerAuth')->user()->role_id,
                'user_belongsto_role_relationship'     => app('VoyagerAuth')->user()->role_id,
                'user_belongstomany_role_relationship' => app('VoyagerAuth')->user()->roles->pluck('id')->toArray(),
            ]);
        }
        $user = User::where('id', $id)->first();
        if ($user->email != $request['email'])
        {
            if (User::where('email', $request['email'])->where('id', '!=', $id)->count() > 0)
            {
                $messages["error"] = 'El correo indicado pertenece a otro usuario';
                return redirect()->back()->withErrors($messages)->withInput();
            }
        }
        if (Auth::user()->tipo == 'Profesor' && $request['avatar'] != '')
        {
            $messages["error"] = 'Contacte al administrador para cambiar la foto del perfil';
            return redirect()->back()->withErrors($messages)->withInput();
        }
        if ($user->activo)
            $request['activo'] = true;
        else
            $request['activo'] = false;
        $request['tipo'] = $user->tipo;

        list($name1, $name2, $last1, $last2) = array_pad( explode(" ", $request['name'], 4), 4, '');
        if (($name1 == '' || $name2 == '') && $user->tipo == 'Profesor')
        {
            $messages["error"] = 'Por favor, escriba al menos un nombre y un apellido';
            return redirect()->back()->withErrors($messages)->withInput();
        }
        $name = $name1;
        if ($last1 == '')
            $last = $name2;
        else 
        {
            $name = $name.' '.$name2;
            $last = $last1;
        }
        if ($last2 != '')
            $last = $last.' '.$last2;

        parent::update($request, $id);

        $profesor = Profesore::where('user_id', $id)->first();
        if ($profesor != null)
        {
            if ($profesor->nombres.' '.$profesor->apellidos != $request['name'])
                Profesore::where('user_id', $id)->update(['nombres' => $name, 'apellidos' => $last]);
            if ($profesor->correo != $request['email'])
                    Profesore::where('user_id', $id)->update(['correo' => $request['email']]);
        }
        if (Auth::user()->tipo == 'Profesor')
            return Voyager::view('voyager::profile');
        else
            return parent::index($request);
    }
}
