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

use Auth;
use App\User;
use App\Ciudad;
use App\Profesore;
use App\Pago;
use App\Multa;
use App\Calendar;
use Validator;
use App\Mail\Notificacion;

class ProfesoresController extends Controller
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
                if (Auth::user()->tipo == 'Profesor')
                    $query = $model::where('user_id', Auth::user()->id)->select('*');
                else
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

        $userID = $id;
        $pagos = Calendar::where('user_id', $userID)->get();
        if ($pagos == null) {
            $pagos = Pago::where('user_id', $userID)->where('estado', 'Solicitado')->get();
        }  

        $valorTotal = $pagos->sum('valor');

        return Voyager::view($view, compact('dataType', 'dataTypeContent', 'isModelTranslatable', 'isSoftDeleted', 'valorTotal'));
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

        $activo = $request['activo'] == 'on' ? 1 : 0;
        $rechazado = $request['rechazado'] == 'on' ? 1 : 0;
        if (Auth::user()->tipo == 'Profesor')
        {
            if ($request['valor_clase'] != $data['valor_clase'])
            {
                $messages["error"] = 'No puede modificar el Valor de la Clase';
                return redirect()->back()->withErrors($messages)->withInput();
            }
            if ($request['valor_tarea'] != $data['valor_tarea'])
            {
                $messages["error"] = 'No puede modificar el Valor de la Tarea';
                return redirect()->back()->withErrors($messages)->withInput();
            }
            if ($activo != $data['activo'])
            {
                $messages["error"] = 'No puede modificar el estado Activo';
                return redirect()->back()->withErrors($messages)->withInput();
            }
            if ($rechazado != $data['rechazado'])
            {
                $messages["error"] = 'No puede modificar el estado Rechazado';
                return redirect()->back()->withErrors($messages)->withInput();
            }
        }
        if ($rechazado && $activo)
        {
            $messages["error"] = 'No puede estar Activo y Rechazado al mismo tiempo';
            return redirect()->back()->withErrors($messages)->withInput();
        }

        // Validate fields with ajax
        $val = $this->validateBread($request->all(), $dataType->editRows, $dataType->name, $id)->validate();
        
        if ($request['activo'] && !$data['activo'])
        {
            try 
            {
                Mail::to($data['correo'])->send(new Notificacion($data['nombres'], 
                        'Felicitaciones! Tu perfil ha sido ', 'Aprobado', 'Bienvenido!', env('EMPRESA')));
            }
            catch (Exception $e) 
            {
                $messages["error"] = 'Actualización realizada pero No se ha podido enviar el correo';
                return redirect()->back()->withErrors($messages)->withInput();
            }
        }
        if ($request['rechazado'] && !$data['rechazado'])
        {
            try 
            {
                Mail::to($data['correo'])->send(new Notificacion($data['nombres'], 
                        'Lo sentimos tu perfil no ha sido ', 'Aprobado', 'Contáctanos para mayor información', env('EMPRESA')));
            }
            catch (Exception $e) 
            {
                $messages["error"] = 'Actualización realizada pero No se ha podido enviar el correo';
                return redirect()->back()->withErrors($messages)->withInput();
            }
        }

        $this->insertUpdateData($request, $slug, $dataType->editRows, $data);
        event(new BreadDataUpdated($dataType, $data));
/*
        $v = \Validator::make($request->all(), [
            'correo'    => 'required|email',
        ]);
        if ($v->fails())
        {
            $messages["error"] = 'Correo electrónico Inválido';
            return redirect()->back()->withErrors($messages)->withInput();
        }
        $user = User::where('email', $data['correo'])->select('*')->first();
        if ($user != null && $user->id != $id)
        {
            $messages["error"] = 'Correo electrónico no disponible';
            return redirect()->back()->withErrors($messages)->withInput();
        }
        $user = User::where('id', $id)->select('*')->first();
        $dataUser = [
            "name" => $data['nombres'].' '.$data['apellidos'], "email" => $data['correo'],
        ];
        $actualizado = User::where('id', $id )->update( $dataUser );
        */

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

        $v = \Validator::make($request->all(), [
            'correo'  => 'required|email',
        ]);
        if ($v->fails())
        {
            $messages["error"] = 'Correo Inválido';
            return redirect()->back()->withErrors($messages)->withInput();
        }
        else
        {
            $posArroba = strpos($request['correo'], '@');
            $posPunto = strripos($request['correo'], '.');
            if ($posArroba === false || $posPunto === false || $posArroba > $posPunto)
            {
                $messages["error"] = 'Correo Inválido';
                return redirect()->back()->withErrors($messages)->withInput();
            }
        }
        $user = User::where('email', $request['correo'])->select('*')->first();
        if ($user != null)
        {
            $messages["error"] = 'Correo electrónico no disponible';
            return redirect()->back()->withErrors($messages)->withInput();
        }
        $v = \Validator::make($request->all(), [
            'nombres' => 'required|min:3|max:50',
            'apellidos' => 'required|min:3|max:50',
            'apodo' => 'required|min:3|max:20',
        ]);
        if ($v->fails())
        {
            $messages["error"] = 'Complete los Datos: Nombres, Apellidos y Apodo';
            return redirect()->back()->withErrors($messages)->withInput();
        }
        $cedula = isset($request['cedula']) ? trim($request['cedula']) : NULL;
        if (strlen($cedula) != 10 && strlen($cedula) != 0)
        {
            $messages["error"] = 'Cédula inválida';
            return redirect()->back()->withErrors($messages)->withInput();
        }
        $v = \Validator::make($request->all(), [
            'celular' => 'required',
        ]);
        if ($v->fails())
        {
            $messages["error"] = 'Complete los Datos: Celular';
            return redirect()->back()->withErrors($messages)->withInput();
        }
        $v = \Validator::make($request->all(), [
            'ubicacion' => 'required',
            'ciudad' => 'required',
        ]);
        if ($v->fails())
        {
            $messages["error"] = 'Complete los Datos: Ubicación y Ciudad';
            return redirect()->back()->withErrors($messages)->withInput();
        }
        $ciudad = Ciudad::where('ciudad', '=', $request['ciudad'] )->first();
        if (!$ciudad)
        {
            $messages["error"] = 'Ciudad inválida';
            return redirect()->back()->withErrors($messages)->withInput();
        }

        $newUser = User::create([
            'role_id' => 4,
            'name' => $request['nombres'].' '.$request['apellidos'],
            'email' => $request['correo'],
            'avatar' => 'users/default.png',
            'password' => bcrypt('1234567890'),
            'tipo' => 'Profesor',
            'activo' => true
        ]);;
        if (!$newUser)
        {
            $messages["error"] = 'Error al crear usuario';
            return redirect()->back()->withErrors($messages)->withInput();
        }
        $data = Profesore::create([
            'user_id' => $newUser->id,
            'celular' => $request['celular'],
            'correo' => $newUser->email,
            'nombres' => $request['nombres'],
            'apellidos' => $request['apellidos'],
            'cedula' => $cedula,
            'apodo' => $request['apodo'],
            'ubicacion' => $request['ubicacion'],
            'ciudad' => $request['ciudad'],
            'clases' => $request['clases'] ? true : false,
            'tareas' => $request['tareas'] ? true : false,
            'disponible' => $request['disponible'] ? true : false,
            'hoja_vida ' => $request['hoja_vida'],
            'titulo ' => $request['titulo'],
            'activo' => $request['activo'] ? true : false,
            'created_at' => $request['created_at'],
            'updated_at' => $request['created_at'],
            'cuenta' => $request['cuenta'],
            'banco' => $request['banco'],
            'tipo_cuenta' => $request['tipo_cuenta'],
            'descripcion' => $request['descripcion'],
            'valor_clase' => $request['valor_clase'],
            'valor_tarea' => $request['valor_tarea'],
            'fecha_nacimiento' => date('Y-m-d', strtotime($request['fecha_nacimiento'])),
            'genero' => $request['genero']
        ]);
        if (!$data)
        {
            $messages["error"] = 'Error al crear Profesor';
            return redirect()->back()->withErrors($messages)->withInput();
        }
        event(new BreadDataAdded($dataType, $data));
        unset($request['fecha_nacimiento']);
        $request['fecha_nacimiento'] = $data->fecha_nacimiento;
        $this->insertUpdateData($request, $slug, $dataType->editRows, $data);

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

    public function pagar()
    {
        try
        {
            $userID = \Request::get('user_id');
            $pagos = Calendar::where('user_id', $userID)->get(); 
            if ($pagos == null) {
                $pagos = Pago::where('user_id', $userID)->where('estado', 'Solicitado')->get();
                foreach ($pagos as $item)
                {
                    Pago::where('id', $item->id)->update(['estado' => 'Aprobado']);
                }
            }
            // $pagos = Pago::where('user_id', $userID)->where('estado', 'Solicitado')->get(); 
            // foreach ($pagos as $item)
            // {
            //     Pago::where('id', $item->id)->update(['estado' => 'Aprobado']);
            // }
            // \Request::session()->flash('success', 'Pagos realizados por un valor de $ '.$pagos->sum('valor'));
            return redirect()->back()->with([
                'message'    => 'Pagos realizados por un valor de $ '.$pagos->sum('valor'),
                'alert-type' => 'success',
            ]);
        }
        catch (Exception $e) 
        {
            // \Request::session()->flash('error', 'No se pudieron realizar los Pagos.');
            return redirect()->back()->with([
                'message'    => 'No se pudieron realizar los Pagos.',
                'alert-type' => 'error',
            ]);
        }
        return redirect()->back();
    }

    public function multar()
    {
        try
        {
            $userID = \Request::get('user_id');
            $multas = Multa::where('user_id', $userID)->where('estado', 'Solicitado')->get(); 
            foreach ($multas as $item)
            {
                Multa::where('id', $item->id)->update(['estado' => 'Aprobado']);
            }
            // \Request::session()->flash('success', 'Multas realizados por un valor de $ '.$multas->sum('valor'));
            return redirect()->back()->with([
                'message'    => 'Multas realizados por un valor de $ '.$multas->sum('valor'),
                'alert-type' => 'success',
            ]);
        }
        catch (Exception $e) 
        {
            // \Request::session()->flash('error', 'No se pudieron realizar las Multas.');
            return redirect()->back()->with([
                'message'    => 'No se pudieron realizar las Multas.',
                'alert-type' => 'error',
            ]);
        }
        // return redirect()->back();
    }

    public function updateOrCreate(Request $request)
    {
        $userID = $request['user_id'];
        $startDate = $request['startDate'];
        $endDate = $request['endDate'];
        // Calendar::where('user_id', $userID)->delete();
        Calendar::truncate();
        if ($startDate != '' &&  $endDate != '') {
            $startDate = date_format(date_create($startDate), "Y-m-d H:i:s");
            $endDate = date_format(date_create($endDate), "Y-m-d H:i:s");
            $pagos = Pago::where('user_id', $userID)->where('estado', 'Solicitado')
            ->where('created_at', '>=', $startDate)
            ->where('created_at', '<=', $endDate)->get();         

            $valorTotal = $pagos->sum('valor');

                Calendar::create([
                    'user_id' => $userID,
                    'start_date' => isset($startDate) ? $startDate : null,
                    'end_date' => isset($endDate) ? $endDate : null,
                    'valor' => $valorTotal,
                ]);
            
            
        } else {
            $pagos = Pago::where('user_id', $userID)->where('estado', 'Solicitado')->get();         
            $valorTotal = $pagos->sum('valor');

            Calendar::create([
                'user_id' => $userID,
                'start_date' => null,
                'end_date' => null,
                'valor' => $valorTotal,
            ]);
        }

        return redirect()->back();
    }

}