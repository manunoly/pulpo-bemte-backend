<?php

namespace App\Http\Controllers\Voyager;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use TCG\Voyager\Database\Schema\SchemaManager;
use TCG\Voyager\Events\BreadDataAdded;
use TCG\Voyager\Events\BreadDataDeleted;
use TCG\Voyager\Events\BreadDataRestored;
use TCG\Voyager\Events\BreadDataUpdated;
use TCG\Voyager\Events\BreadImagesDeleted;
use TCG\Voyager\Facades\Voyager;
use Illuminate\Support\Facades\Mail;
use TCG\Voyager\Http\Controllers\Traits\BreadRelationshipParser;

use App\User;
use App\Tarea;
use App\Clase;
use App\Pago;
use App\Profesore;
use App\AlumnoCompra;
use App\AlumnoPago;
use App\Alumno;
use App\NotificacionesPushFcm;
use App\Mail\Notificacion;
use App\Mail\NotificacionClases;
use App\Mail\NotificacionTareas;

class AlumnoPagoController extends Controller
{
    use BreadRelationshipParser;

    //***************************************
    //               ____
    //              |  _ \
    //              | |_) |
    //              |  _ <
    //              | |_) |
    //              |____/
    //
    //      Browse our Data Type (B)READ
    //
    //****************************************

    public function index(Request $request)
    {
        // GET THE SLUG, ex. 'posts', 'pages', etc.
        $slug = $this->getSlug($request);

        // GET THE DataType based on the slug
        $dataType = Voyager::model('DataType')->where('slug', '=', $slug)->first();

        // Check permission
        $this->authorize('browse', app($dataType->model_name));

        $getter = $dataType->server_side ? 'paginate' : 'get';

        $search = (object) ['value' => $request->get('s'), 'key' => $request->get('key'), 'filter' => $request->get('filter')];
        $searchable = $dataType->server_side ? array_keys(SchemaManager::describeTable(app($dataType->model_name)->getTable())->toArray()) : '';
        $orderBy = $request->get('order_by', $dataType->order_column);
        $sortOrder = $request->get('sort_order', null);
        $usesSoftDeletes = false;
        $showSoftDeleted = false;
        $orderColumn = [];
        if ($orderBy) {
            $index = $dataType->browseRows->where('field', $orderBy)->keys()->first() + 1;
            $orderColumn = [[$index, 'desc']];
            if (!$sortOrder && isset($dataType->order_direction)) {
                $sortOrder = $dataType->order_direction;
                $orderColumn = [[$index, $dataType->order_direction]];
            } else {
                $orderColumn = [[$index, 'desc']];
            }
        }

        // Next Get or Paginate the actual content from the MODEL that corresponds to the slug DataType
        if (strlen($dataType->model_name) != 0) {
            $model = app($dataType->model_name);

            if ($dataType->scope && $dataType->scope != '' && method_exists($model, 'scope'.ucfirst($dataType->scope))) {
                $query = $model->{$dataType->scope}();
            } else {
                $query = $model::select('*');
            }

            // Use withTrashed() if model uses SoftDeletes and if toggle is selected
            if ($model && in_array(SoftDeletes::class, class_uses($model)) && app('VoyagerAuth')->user()->can('delete', app($dataType->model_name))) {
                $usesSoftDeletes = true;

                if ($request->get('showSoftDeleted')) {
                    $showSoftDeleted = true;
                    $query = $query->withTrashed();
                }
            }

            // If a column has a relationship associated with it, we do not want to show that field
            $this->removeRelationshipField($dataType, 'browse');

            if ($search->value != '' && $search->key && $search->filter) {
                $search_filter = ($search->filter == 'equals') ? '=' : 'LIKE';
                $search_value = ($search->filter == 'equals') ? $search->value : '%'.$search->value.'%';
                $query->where($search->key, $search_filter, $search_value);
            }

            if ($orderBy && in_array($orderBy, $dataType->fields())) {
                $querySortOrder = (!empty($sortOrder)) ? $sortOrder : 'desc';
                $dataTypeContent = call_user_func([
                    $query->orderBy($orderBy, $querySortOrder),
                    $getter,
                ]);
            } elseif ($model->timestamps) {
                $dataTypeContent = call_user_func([$query->latest($model::CREATED_AT), $getter]);
            } else {
                $dataTypeContent = call_user_func([$query->orderBy($model->getKeyName(), 'DESC'), $getter]);
            }

            // Replace relationships' keys for labels and create READ links if a slug is provided.
            $dataTypeContent = $this->resolveRelations($dataTypeContent, $dataType);
        } else {
            // If Model doesn't exist, get data from table name
            $dataTypeContent = call_user_func([DB::table($dataType->name), $getter]);
            $model = false;
        }

        // Check if BREAD is Translatable
        if (($isModelTranslatable = is_bread_translatable($model))) {
            $dataTypeContent->load('translations');
        }

        // Check if server side pagination is enabled
        $isServerSide = isset($dataType->server_side) && $dataType->server_side;

        // Check if a default search key is set
        $defaultSearchKey = $dataType->default_search_key ?? null;

        $view = 'voyager::bread.browse';

        if (view()->exists("voyager::$slug.browse")) {
            $view = "voyager::$slug.browse";
        }

        return Voyager::view($view, compact(
            'dataType',
            'dataTypeContent',
            'isModelTranslatable',
            'search',
            'orderBy',
            'orderColumn',
            'sortOrder',
            'searchable',
            'isServerSide',
            'defaultSearchKey',
            'usesSoftDeletes',
            'showSoftDeleted'
        ));
    }

    //***************************************
    //                _____
    //               |  __ \
    //               | |__) |
    //               |  _  /
    //               | | \ \
    //               |_|  \_\
    //
    //  Read an item of our Data Type B(R)EAD
    //
    //****************************************

    public function show(Request $request, $id)
    {
        $slug = $this->getSlug($request);

        $dataType = Voyager::model('DataType')->where('slug', '=', $slug)->first();

        $isSoftDeleted = false;

        if (strlen($dataType->model_name) != 0) {
            $model = app($dataType->model_name);

            // Use withTrashed() if model uses SoftDeletes and if toggle is selected
            if ($model && in_array(SoftDeletes::class, class_uses($model))) {
                $model = $model->withTrashed();
            }
            if ($dataType->scope && $dataType->scope != '' && method_exists($model, 'scope'.ucfirst($dataType->scope))) {
                $model = $model->{$dataType->scope}();
            }
            $dataTypeContent = call_user_func([$model, 'findOrFail'], $id);
            if ($dataTypeContent->deleted_at) {
                $isSoftDeleted = true;
            }
        } else {
            // If Model doest exist, get data from table name
            $dataTypeContent = DB::table($dataType->name)->where('id', $id)->first();
        }

        // Replace relationships' keys for labels and create READ links if a slug is provided.
        $dataTypeContent = $this->resolveRelations($dataTypeContent, $dataType, true);

        // If a column has a relationship associated with it, we do not want to show that field
        $this->removeRelationshipField($dataType, 'read');

        // Check permission
        $this->authorize('read', $dataTypeContent);

        // Check if BREAD is Translatable
        $isModelTranslatable = is_bread_translatable($dataTypeContent);

        $view = 'voyager::bread.read';

        if (view()->exists("voyager::$slug.read")) {
            $view = "voyager::$slug.read";
        }

        return Voyager::view($view, compact('dataType', 'dataTypeContent', 'isModelTranslatable', 'isSoftDeleted'));
    }

    //***************************************
    //                ______
    //               |  ____|
    //               | |__
    //               |  __|
    //               | |____
    //               |______|
    //
    //  Edit an item of our Data Type BR(E)AD
    //
    //****************************************

    public function edit(Request $request, $id)
    {
        $slug = $this->getSlug($request);

        $dataType = Voyager::model('DataType')->where('slug', '=', $slug)->first();

        if (strlen($dataType->model_name) != 0) {
            $model = app($dataType->model_name);

            // Use withTrashed() if model uses SoftDeletes and if toggle is selected
            if ($model && in_array(SoftDeletes::class, class_uses($model))) {
                $model = $model->withTrashed();
            }
            if ($dataType->scope && $dataType->scope != '' && method_exists($model, 'scope'.ucfirst($dataType->scope))) {
                $model = $model->{$dataType->scope}();
            }
            $dataTypeContent = call_user_func([$model, 'findOrFail'], $id);
        } else {
            // If Model doest exist, get data from table name
            $dataTypeContent = DB::table($dataType->name)->where('id', $id)->first();
        }

        foreach ($dataType->editRows as $key => $row) {
            $dataType->editRows[$key]['col_width'] = isset($row->details->width) ? $row->details->width : 100;
        }

        // If a column has a relationship associated with it, we do not want to show that field
        $this->removeRelationshipField($dataType, 'edit');

        // Check permission
        $this->authorize('edit', $dataTypeContent);

        // Check if BREAD is Translatable
        $isModelTranslatable = is_bread_translatable($dataTypeContent);

        $view = 'voyager::bread.edit-add';

        if (view()->exists("voyager::$slug.edit-add")) {
            $view = "voyager::$slug.edit-add";
        }

        return Voyager::view($view, compact('dataType', 'dataTypeContent', 'isModelTranslatable'));
    }

    // POST BR(E)AD
    public function update(Request $request, $id)
    {
        $slug = $this->getSlug($request);

        $dataType = Voyager::model('DataType')->where('slug', '=', $slug)->first();

        // Compatibility with Model binding.
        $id = $id instanceof Model ? $id->{$id->getKeyName()} : $id;

        $model = app($dataType->model_name);
        if ($dataType->scope && $dataType->scope != '' && method_exists($model, 'scope'.ucfirst($dataType->scope))) {
            $model = $model->{$dataType->scope}();
        }
        if ($model && in_array(SoftDeletes::class, class_uses($model))) {
            $data = $model->withTrashed()->findOrFail($id);
        } else {
            $data = call_user_func([$dataType->model_name, 'findOrFail'], $id);
        }

        // Check permission
        $this->authorize('edit', $data);

        // Validate fields with ajax
        $val = $this->validateBread($request->all(), $dataType->editRows, $dataType->name, $id)->validate();
        
        if (($request['estado'] == null) || ($request['estado'] == 'Solicitado') || ($request['estado'] == 'Cancelado'))
        {
            $messages["error"] = 'Por favor Apruebe o Rechace el pago';
            return redirect()->back()->withErrors($messages)->withInput();
        }
        $pago = AlumnoPago::where('id', $id)->first();
        if ($pago != null && ($pago->estado == 'Aprobado' || $pago->estado == 'Rechazado'))
        {
            $messages["error"] = 'El Pago ya fue procesado';
            return redirect()->back()->withErrors($messages)->withInput();
        }
        else if ($pago == null)
        {
            $messages["error"] = 'El Pago no existe';
            return redirect()->back()->withErrors($messages)->withInput();
        }
        $duracion = 0;
        $compra = null;
        $tarea = Tarea::where('id', $data['tarea_id'])->first();
        if (($tarea != null) && ($tarea->estado != 'Confirmando_Pago'))
        {
            $messages["error"] = 'La Tarea ya no permite la Aprobación del pago';
            return redirect()->back()->withErrors($messages)->withInput();
        }
        else if (($tarea != null) && ($tarea->user_canc != null) && $request['estado'] == 'Aprobado')
        {
            $messages["error"] = 'La Tarea ha sido cancelada, no puede ser pagada';
            return redirect()->back()->withErrors($messages)->withInput();
        }
        else if ($tarea != null)
        {
            $duracion = $tarea->tiempo_estimado;
            if ($tarea->compra_id > 0)
                $compra = AlumnoCompra::where('id', $clase->compra_id )->first();
        }
        else if ($data['tarea_id'] > 0)
        {
            $messages["error"] = 'La Tarea no existe';
            return redirect()->back()->withErrors($messages)->withInput();
        }
        $clase = Clase::where('id', $data['clase_id'])->first();
        if (($clase != null) && ($clase->estado != 'Confirmando_Pago'))
        {
            $messages["error"] = 'La Clase ya no permite la Aprobación del pago';
            return redirect()->back()->withErrors($messages)->withInput();
        }
        else if (($clase != null) && ($clase->user_canc != null) && $request['estado'] == 'Aprobado')
        {
            $messages["error"] = 'La Clase ha sido cancelada, no puede ser pagada';
            return redirect()->back()->withErrors($messages)->withInput();
        }
        else if ($clase != null)
        {
            $duracion = $clase->duracion + ($clase->personas - 1);
            if ($clase->compra_id > 0)
                $compra = AlumnoCompra::where('id', $clase->compra_id )->first();
        }
        else if ($data['clase_id'] > 0)
        {
            $messages["error"] = 'La Clase no existe';
            return redirect()->back()->withErrors($messages)->withInput();
        }
        $billetera = Alumno::where('user_id', $data['user_id'])->first();
        if ($billetera == null)
        {
            $messages["error"] = 'El Alumno no existe';
            return redirect()->back()->withErrors($messages)->withInput();
        }
        if ($compra == null)
            $compra = AlumnoCompra::where('id', $data['combo_id'])->first();
        if (($compra != null) && ($compra->estado != 'Solicitado'))
        {
            $messages["error"] = 'La Compra del combo ya no permite la Aprobación del pago';
            return redirect()->back()->withErrors($messages)->withInput();
        }
        else if ($compra != null)
        {
            if ($billetera->billetera + $compra->horas - $duracion < 0)
            {
                $messages["error"] = 'La Horas compradas del combo no son suficientes para pagar';
                return redirect()->back()->withErrors($messages)->withInput();
            }
        }
        elseif ($data['combo_id'] > 0)
        {
            $messages["error"] = 'La Compra del Combo no existe';
            return redirect()->back()->withErrors($messages)->withInput();
        }
        $dataAct['estado'] = 'Aceptado';
        if ($request['estado'] != 'Aprobado')
            $dataAct['activa'] = false;
        if ($tarea != null)
        {
            if ($request['estado'] != 'Aprobado')
                $dataAct['estado'] = 'Pago_Rechazado';
            else
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
                    return redirect()->back()->withErrors($messages)->withInput();
                }
            }
            $actualizado = Tarea::where('id', $tarea->id )->update( $dataAct );
            if(!$actualizado )
            {
                $messages["error"] = 'Ocurrió un error al actualizar Tarea';
                return redirect()->back()->withErrors($messages)->withInput();
            }
            $userAlumno = User::where('id', $tarea->user_id)->first();
            $userProf = User::where('id', $tarea->user_id_pro)->first();
            if ($request['estado'] == 'Aprobado')
            {
                try 
                {
                    Mail::to($userAlumno->email)->send(new NotificacionTareas($tarea, 'Transferencia', '', '', $userAlumno->name, $userProf->name, 
                                                    env('EMPRESA'), true));
                    Mail::to($userProf->email)->send(new NotificacionTareas($tarea, 'Transferencia', '', '', $userAlumno->name, $userProf->name, 
                                                    env('EMPRESA'), false));
                }
                catch (Exception $e) 
                {
                    $messages["error"] = 'No se ha podido enviar el correo';
                    return redirect()->back()->withErrors($messages)->withInput();
                } 
            }
            else
            {
                try 
                {
                    $textoE = ' Imagen borrosa o incorrecta. Si cree que ha sido un error, escriba al correo '.env('CORREO');
                    Mail::to($userAlumno->email)->send(new Notificacion($userAlumno->name, 
                            'Su Pago para la Tarea de '.$tarea->materia.', '.$tarea->tema.', ha sido Rechazado.', '',
                            $textoE, env('EMPRESA')));
                    Mail::to($userProf->email)->send(new Notificacion($userProf->name, 
                            'Lo sentimos la Tarea de '.$tarea->materia.', '.$tarea->tema.', no ha sido Confirmada.', '',
                            '', env('EMPRESA')));
                }
                catch (Exception $e) 
                {
                    $messages["error"] = 'No se ha podido enviar el correo';
                    return redirect()->back()->withErrors($messages)->withInput();
                }
            }
            //enviar notificacion al profesor y al alumno
            $notificacion['titulo'] = 'Tarea '.$request['estado'];
            $notificacion['color'] = "alumno";
            $notificacion['texto'] = 'El pago de la Tarea de '.$tarea->materia.', '.$tarea->tema.', ha sido '.$request['estado'];
            if ($request['estado'] == 'Aprobado')
                $notificacion['texto'] = $notificacion['texto'].'. La Tarea ha sido Asignada';
            else
            {
                $notificacion['texto'] = $notificacion['texto'].'. Imagen borrosa o incorrecta. Si cree que ha sido un error, escriba al correo '.env('CORREO');
                $notificacion['color'] = "cancelar";
            }
            $notificacion['estado'] = 'NO';
            $notificacion['tarea_id'] = $tarea->id;
            $notificacion['clase_id'] = 0;
            $notificacion['chat_id'] = 0;
            $notificacion['compra_id'] = 0;
            $pushClass = new NotificacionesPushFcm();
            $pushClass->enviarNotificacion($notificacion, $userAlumno);
            $notificacion['color'] = "profesor";
            if ($request['estado'] == 'Aprobado')
                $notificacion['texto'] = 'La Tarea de '.$tarea->materia.', '.$tarea->tema.', ha sido Confirmada.';
            else
            {
                $notificacion['texto'] = 'Lo sentimos la Tarea de '.$tarea->materia.', '.$tarea->tema.', no ha sido Confirmada.';
                $notificacion['color'] = "cancelar";
            }
            $pushClass->enviarNotificacion($notificacion, $userProf);
        }
        if ($clase != null)
        {
            if ($request['estado'] != 'Aprobado')
                $dataAct['estado'] = 'Pago_Rechazado';
            else
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
                    return redirect()->back()->withErrors($messages)->withInput();
                }
            }
            $actualizado = Clase::where('id', $clase->id )->update( $dataAct );
            if(!$actualizado )
            {
                $messages["error"] = 'Ocurrió un error al actualizar Clase';
                return redirect()->back()->withErrors($messages)->withInput();
            }
            $userAlumno = User::where('id', $clase->user_id)->first();
            $userProf = User::where('id', $clase->user_id_pro)->first();
            if ($request['estado'] == 'Aprobado')
            {
                try 
                {
                    Mail::to($userAlumno->email)->send(new NotificacionClases($clase, 'Transferencia', '', '', $userAlumno->name, $userProf->name, 
                                                    env('EMPRESA'), true));
                    Mail::to($userProf->email)->send(new NotificacionClases($clase,  'Transferencia', '', '', $userAlumno->name, $userProf->name, 
                                                    env('EMPRESA'), false));
                }
                catch (Exception $e) 
                {
                    $messages["error"] = 'No se ha podido enviar el correo';
                    return redirect()->back()->withErrors($messages)->withInput();
                }
            }
            else
            {
                try 
                {
                    $textoE = ' Imagen borrosa o incorrecta. Si cree que ha sido un error, escriba al correo '.env('CORREO');
                    Mail::to($userAlumno->email)->send(new Notificacion($userAlumno->name, 
                            'Su Pago para la Clase de '.$clase->materia.', '.$clase->tema.', ha sido Rechazada.', '',
                            $textoE, env('EMPRESA')));
                    Mail::to($userProf->email)->send(new Notificacion($userProf->name, 
                            'Lo sentimos la Clase de '.$clase->materia.', '.$clase->tema.', no ha sido Confirmada.', '',
                            '', env('EMPRESA')));
                }
                catch (Exception $e) 
                {
                    $messages["error"] = 'No se ha podido enviar el correo';
                    return redirect()->back()->withErrors($messages)->withInput();
                }
            }
            //enviar notificacion al profesor y al alumno
            $notificacion['titulo'] = 'Clase '.$request['estado'];
            $notificacion['color'] = "alumno";
            $notificacion['texto'] = 'El pago de la Clase de '.$clase->materia.', '.$clase->tema.', ha sido '.$request['estado'];
            if ($request['estado'] == 'Aprobado')
                $notificacion['texto'] = $notificacion['texto'].'. La Clase ha sido Asignada';
            else
            {
                $notificacion['color'] = "cancelar";
                $notificacion['texto'] = $notificacion['texto'].'. Imagen borrosa o incorrecta. Si cree que ha sido un error, escriba al correo '.env('CORREO');
            }
            $notificacion['estado'] = 'NO';
            $notificacion['clase_id'] = $clase->id;
            $notificacion['tarea_id'] = 0;
            $notificacion['chat_id'] = 0;
            $notificacion['compra_id'] = 0;
            $pushClass = new NotificacionesPushFcm();
            $pushClass->enviarNotificacion($notificacion, $userAlumno);
            $notificacion['color'] = "profesor";
            if ($request['estado'] == 'Aprobado')
                $notificacion['texto'] = 'La Clase de '.$clase->materia.', '.$clase->tema.', ha sido Confirmada.';
            else
            {
                $notificacion['color'] = "cancelar";
                $notificacion['texto'] = 'Lo sentimos la Clase de '.$clase->materia.', '.$clase->tema.', no ha sido Confirmada.';
            }
            $pushClass->enviarNotificacion($notificacion, $userProf);
        }
        if ($compra != null)
        {
            if ($request['estado'] != 'Aprobado')
                $dataActCompra['estado'] = 'Rechazado';
            else
                $dataActCompra['estado'] = 'Aceptado';
            $actualizado = AlumnoCompra::where('id', $compra->id )->update( $dataActCompra );
            if(!$actualizado )
            {
                $messages["error"] = 'Ocurrió un error al actualizar Compra';
                return redirect()->back()->withErrors($messages)->withInput();
            }
            if ($request['estado'] == 'Aprobado')
            {
                $dataBill['billetera'] = $billetera->billetera + $compra->horas - $duracion;
                $actualizado = Alumno::where('user_id', $billetera->user_id)->update( $dataBill );
                if(!$actualizado )
                {
                    $messages["error"] = 'Ocurrió un error al actualizar Billetera';
                    return redirect()->back()->withErrors($messages)->withInput();
                }
            }
            //enviar notificacion al alumno
            $userAlumno = User::where('id', $compra->user_id)->first();
            $notificacion['titulo'] = 'Pago Horas '.$request['estado'];
            $notificacion['texto'] = 'El pago de '.$compra->horas.' Horas ha sido '.$request['estado'].'. Por favor,';
            $notificacion['color'] = "alumno";
            if ($request['estado'] == 'Aprobado')
                $notificacion['texto'] = $notificacion['texto'].' revise su Billetera';
            else
            {
                $notificacion['color'] = "cancelar";
                $notificacion['texto'] = $notificacion['texto'].' contactar con el administrador';
            }
            $notificacion['estado'] = 'NO';
            $notificacion['clase_id'] = 0;
            $notificacion['tarea_id'] = 0;
            $notificacion['chat_id'] = 0;
            $notificacion['compra_id'] = $compra->id;
            $pushClass = new NotificacionesPushFcm();
            $pushClass->enviarNotificacion($notificacion, $userAlumno);
        }
        
        $this->insertUpdateData($request, $slug, $dataType->editRows, $data);

        event(new BreadDataUpdated($dataType, $data));

        return redirect()
        ->route("voyager.{$dataType->slug}.index")
        ->with([
            'message'    => __('voyager::generic.successfully_updated')." {$dataType->display_name_singular}",
            'alert-type' => 'success',
        ]);
    }

    //***************************************
    //
    //                   /\
    //                  /  \
    //                 / /\ \
    //                / ____ \
    //               /_/    \_\
    //
    //
    // Add a new item of our Data Type BRE(A)D
    //
    //****************************************

    public function create(Request $request)
    {
        $slug = $this->getSlug($request);

        $dataType = Voyager::model('DataType')->where('slug', '=', $slug)->first();

        // Check permission
        $this->authorize('add', app($dataType->model_name));

        $dataTypeContent = (strlen($dataType->model_name) != 0)
                            ? new $dataType->model_name()
                            : false;

        foreach ($dataType->addRows as $key => $row) {
            $dataType->addRows[$key]['col_width'] = $row->details->width ?? 100;
        }

        // If a column has a relationship associated with it, we do not want to show that field
        $this->removeRelationshipField($dataType, 'add');

        // Check if BREAD is Translatable
        $isModelTranslatable = is_bread_translatable($dataTypeContent);

        $view = 'voyager::bread.edit-add';

        if (view()->exists("voyager::$slug.edit-add")) {
            $view = "voyager::$slug.edit-add";
        }

        return Voyager::view($view, compact('dataType', 'dataTypeContent', 'isModelTranslatable'));
    }

    /**
     * POST BRE(A)D - Store data.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $slug = $this->getSlug($request);

        $dataType = Voyager::model('DataType')->where('slug', '=', $slug)->first();

        // Check permission
        $this->authorize('add', app($dataType->model_name));

        // Validate fields with ajax
        $val = $this->validateBread($request->all(), $dataType->addRows)->validate();
        $data = $this->insertUpdateData($request, $slug, $dataType->addRows, new $dataType->model_name());

        event(new BreadDataAdded($dataType, $data));

        return redirect()
        ->route("voyager.{$dataType->slug}.index")
        ->with([
                'message'    => __('voyager::generic.successfully_added_new')." {$dataType->display_name_singular}",
                'alert-type' => 'success',
            ]);
    }

    //***************************************
    //                _____
    //               |  __ \
    //               | |  | |
    //               | |  | |
    //               | |__| |
    //               |_____/
    //
    //         Delete an item BREA(D)
    //
    //****************************************

    public function destroy(Request $request, $id)
    {
        $slug = $this->getSlug($request);

        $dataType = Voyager::model('DataType')->where('slug', '=', $slug)->first();

        // Check permission
        $this->authorize('delete', app($dataType->model_name));

        // Init array of IDs
        $ids = [];
        if (empty($id)) {
            // Bulk delete, get IDs from POST
            $ids = explode(',', $request->ids);
        } else {
            // Single item delete, get ID from URL
            $ids[] = $id;
        }
        foreach ($ids as $id) {
            $data = call_user_func([$dataType->model_name, 'findOrFail'], $id);

            $model = app($dataType->model_name);
            if (!($model && in_array(SoftDeletes::class, class_uses($model)))) {
                $this->cleanup($dataType, $data);
            }
        }

        $displayName = count($ids) > 1 ? $dataType->display_name_plural : $dataType->display_name_singular;

        $res = $data->destroy($ids);
        $data = $res
            ? [
                'message'    => __('voyager::generic.successfully_deleted')." {$displayName}",
                'alert-type' => 'success',
            ]
            : [
                'message'    => __('voyager::generic.error_deleting')." {$displayName}",
                'alert-type' => 'error',
            ];

        if ($res) {
            event(new BreadDataDeleted($dataType, $data));
        }

        return redirect()->route("voyager.{$dataType->slug}.index")->with($data);
    }

    public function restore(Request $request, $id)
    {
        $slug = $this->getSlug($request);

        $dataType = Voyager::model('DataType')->where('slug', '=', $slug)->first();

        // Check permission
        $this->authorize('delete', app($dataType->model_name));

        // Get record
        $model = call_user_func([$dataType->model_name, 'withTrashed']);
        if ($dataType->scope && $dataType->scope != '' && method_exists($model, 'scope'.ucfirst($dataType->scope))) {
            $model = $model->{$dataType->scope}();
        }
        $data = $model->findOrFail($id);

        $displayName = $dataType->display_name_singular;

        $res = $data->restore($id);
        $data = $res
            ? [
                'message'    => __('voyager::generic.successfully_restored')." {$displayName}",
                'alert-type' => 'success',
            ]
            : [
                'message'    => __('voyager::generic.error_restoring')." {$displayName}",
                'alert-type' => 'error',
            ];

        if ($res) {
            event(new BreadDataRestored($dataType, $data));
        }

        return redirect()->route("voyager.{$dataType->slug}.index")->with($data);
    }

    /**
     * Remove translations, images and files related to a BREAD item.
     *
     * @param \Illuminate\Database\Eloquent\Model $dataType
     * @param \Illuminate\Database\Eloquent\Model $data
     *
     * @return void
     */
    protected function cleanup($dataType, $data)
    {
        // Delete Translations, if present
        if (is_bread_translatable($data)) {
            $data->deleteAttributeTranslations($data->getTranslatableAttributes());
        }

        // Delete Images
        $this->deleteBreadImages($data, $dataType->deleteRows->where('type', 'image'));

        // Delete Files
        foreach ($dataType->deleteRows->where('type', 'file') as $row) {
            if (isset($data->{$row->field})) {
                foreach (json_decode($data->{$row->field}) as $file) {
                    $this->deleteFileIfExists($file->download_link);
                }
            }
        }

        // Delete media-picker files
        $dataType->rows->where('type', 'media_picker')->where('details.delete_files', true)->each(function ($row) use ($data) {
            $content = $data->{$row->field};
            if (isset($content)) {
                if (!is_array($content)) {
                    $content = json_decode($content);
                }
                if (is_array($content)) {
                    foreach ($content as $file) {
                        $this->deleteFileIfExists($file);
                    }
                } else {
                    $this->deleteFileIfExists($content);
                }
            }
        });
    }

    /**
     * Delete all images related to a BREAD item.
     *
     * @param \Illuminate\Database\Eloquent\Model $data
     * @param \Illuminate\Database\Eloquent\Model $rows
     *
     * @return void
     */
    public function deleteBreadImages($data, $rows)
    {
        foreach ($rows as $row) {
            if ($data->{$row->field} != config('voyager.user.default_avatar')) {
                $this->deleteFileIfExists($data->{$row->field});
            }

            if (isset($row->details->thumbnails)) {
                foreach ($row->details->thumbnails as $thumbnail) {
                    $ext = explode('.', $data->{$row->field});
                    $extension = '.'.$ext[count($ext) - 1];

                    $path = str_replace($extension, '', $data->{$row->field});

                    $thumb_name = $thumbnail->name;

                    $this->deleteFileIfExists($path.'-'.$thumb_name.$extension);
                }
            }
        }

        if ($rows->count() > 0) {
            event(new BreadImagesDeleted($data, $rows));
        }
    }

    /**
     * Order BREAD items.
     *
     * @param string $table
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function order(Request $request)
    {
        $slug = $this->getSlug($request);

        $dataType = Voyager::model('DataType')->where('slug', '=', $slug)->first();

        // Check permission
        $this->authorize('edit', app($dataType->model_name));

        if (!isset($dataType->order_column) || !isset($dataType->order_display_column)) {
            return redirect()
            ->route("voyager.{$dataType->slug}.index")
            ->with([
                'message'    => __('voyager::bread.ordering_not_set'),
                'alert-type' => 'error',
            ]);
        }

        $model = app($dataType->model_name);
        if ($model && in_array(SoftDeletes::class, class_uses($model))) {
            $model = $model->withTrashed();
        }
        $results = $model->orderBy($dataType->order_column, $dataType->order_direction)->get();

        $display_column = $dataType->order_display_column;

        $dataRow = Voyager::model('DataRow')->whereDataTypeId($dataType->id)->whereField($display_column)->first();

        $view = 'voyager::bread.order';

        if (view()->exists("voyager::$slug.order")) {
            $view = "voyager::$slug.order";
        }

        return Voyager::view($view, compact(
            'dataType',
            'display_column',
            'dataRow',
            'results'
        ));
    }

    public function update_order(Request $request)
    {
        $slug = $this->getSlug($request);

        $dataType = Voyager::model('DataType')->where('slug', '=', $slug)->first();

        // Check permission
        $this->authorize('edit', app($dataType->model_name));

        $model = app($dataType->model_name);

        $order = json_decode($request->input('order'));
        $column = $dataType->order_column;
        foreach ($order as $key => $item) {
            if ($model && in_array(SoftDeletes::class, class_uses($model))) {
                $i = $model->withTrashed()->findOrFail($item->id);
            } else {
                $i = $model->findOrFail($item->id);
            }
            $i->$column = ($key + 1);
            $i->save();
        }
    }

    public function action(Request $request)
    {
        $slug = $this->getSlug($request);
        $dataType = Voyager::model('DataType')->where('slug', '=', $slug)->first();

        $action = new $request->action($dataType, null);

        return $action->massAction(explode(',', $request->ids), $request->headers->get('referer'));
    }

    /**
     * Get BREAD relations data.
     *
     * @param Request $request
     *
     * @return mixed
     */
    public function relation(Request $request)
    {
        $slug = $this->getSlug($request);
        $page = $request->input('page');
        $on_page = 50;
        $search = $request->input('search', false);
        $dataType = Voyager::model('DataType')->where('slug', '=', $slug)->first();

        foreach ($dataType->editRows as $key => $row) {
            if ($row->field === $request->input('type')) {
                $options = $row->details;
                $skip = $on_page * ($page - 1);

                // If search query, use LIKE to filter results depending on field label
                if ($search) {
                    $total_count = app($options->model)->where($options->label, 'LIKE', '%'.$search.'%')->count();
                    $relationshipOptions = app($options->model)->take($on_page)->skip($skip)
                        ->where($options->label, 'LIKE', '%'.$search.'%')
                        ->get();
                } else {
                    $total_count = app($options->model)->count();
                    $relationshipOptions = app($options->model)->take($on_page)->skip($skip)->get();
                }

                $results = [];
                foreach ($relationshipOptions as $relationshipOption) {
                    $results[] = [
                        'id'   => $relationshipOption->{$options->key},
                        'text' => $relationshipOption->{$options->label},
                    ];
                }

                return response()->json([
                    'results'    => $results,
                    'pagination' => [
                        'more' => ($total_count > ($skip + $on_page)),
                    ],
                ]);
            }
        }

        // No result found, return empty array
        return response()->json([], 404);
    }
}