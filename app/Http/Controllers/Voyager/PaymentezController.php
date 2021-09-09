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

use http\Exception;
use Illuminate\Support\Facades\Validator;
use App\User;
use App\Paymentez;
use App\Transaction;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Middleware;
use App\BemteUtilities;

use App\Mail\Notificacion;
use DateTime;

use Carbon\Carbon;

class PaymentezController extends Controller
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

        $this->insertUpdateData($request, $slug, $dataType->editRows, $data);
        event(new BreadDataUpdated($dataType, $data));

        return redirect()
        ->route("voyager.{$dataType->slug}.index")
        ->with([
            'message'    => __('voyager::generic.successfully_updated')." {$dataType->display_name_singular}",
            'alert-type' => 'success',
        ]);
       
    }

    public function refund(){
        $idCompra = \Request::get('id');
        $refundUrl = BemteUtilities::getUrl() . 'transaction/refund';
        try {
            $compra = Paymentez::where('id', $idCompra)->where('estado', 'Pagado')->first();
            if ($compra) {
                try {
                    $transaction = json_decode($compra->paymentez_transaction);
                    $transactionData = [
                        "transaction" => [
                            "id" => $transaction->id
                        ],
                    ];
                    $client = new \GuzzleHttp\Client();
                    // $request = new \GuzzleHttp\Psr7\Request;
                    $authToken = BemteUtilities::getAuthToken();
                    // return $authToken;
                    if (BemteUtilities::getAuthToken()) {
                        $response = $client->request('POST', $refundUrl, [
                            'headers' => ['Content-Type' => 'application/json', 'Auth-Token' => $authToken],
                            'body' => json_encode($transactionData)
                        ]);
                    } else {
                        // return response()->json(Msg::responseMsg('No se realizó el reembolzo', 'error', 'Error'), 500);
                        return redirect()->back()->with([
                            'message'    => 'No se realizó el reembolzo',
                            'alert-type' => 'error',
                        ]);
                    }
                } catch (BadResponseException $e) {
                    //catch the pay error
                    // return response()->json(Msg::responseMsg('No se realizó el reembolzo', 'error', $e->getMessage()), 500);
                    return redirect()->back()->with([
                        'message'    => 'No se realizó el reembolzo',
                        'alert-type' => 'error',
                    ]);
                }
                $transactionData = json_decode($response->getBody());
                // return $transactionData;
                if (strcmp($transactionData->status, 'success') == 0) {
                    Paymentez::where('id_transaction', $compra->id_transaction)->update(['estado' =>  'Reembolzo']);

                    $userAlumno = User::where('id', $compra->user_id)->first();
                    $correoAdmin = 'Se ha sido realizado un reembolso al usuario '.$userAlumno->name;
                    $detalle = 'Transacción ID: '.$transaction->id. ' Authorization Code: '.$transaction->authorization_code.' Valor: '.$compra->amount;

                    try 
                    {
                        Mail::to($userAlumno->email)->send(new Notificacion(
                            $userAlumno->name, 
                            'Se ha realizado el reembolso de su pago por el valor de $' .$compra->amount .' realizado con tarjeta de crédito.', 'Transacción ID: '.$transaction->id. ' Authorization code: '.$transaction->authorization_code, '', 
                            env('EMPRESA')));

                        Mail::to(env('MAILADMIN'))->send(new Notificacion(
                            'Administrador de '.env('EMPRESA'), 
                            $correoAdmin, $detalle, 'Por favor, revisar la transacción.', 
                            env('EMPRESA')));
                    }
                    catch (Exception $e) 
                    {
                        $messages["error"] = 'No se ha podido enviar el correo';
                        return redirect()->back()->withErrors($messages)->withInput();
                    }
                    
                    //return response()->json(Msg::responseMsg('Compra reembolzada', 'ok', true, true), 202);
                    return redirect()->back()->with([
                        'message'    => 'Compra reembolzada correctamente',
                        'alert-type' => 'success',
                    ]);
                } else {
                    // return response()->json(Msg::responseMsg('Reembolzo rechazado', 'error', 500, false), 500);
                    return redirect()->back()->with([
                        'message'    => 'Reembolzo rechazado',
                        'alert-type' => 'error',
                    ]);
                }
            } else {
                // return response()->json(Msg::responseMsg('No se encontró la referencia de la compra', 'error', 500, false), 500);
                return redirect()->back()->with([
                    'message'    => 'La Compra ya ha sido reembolsada',
                    'alert-type' => 'error',
                ]);
            }
        } catch (Exception $e) {
            // return response()->json(Msg::responseMsg($e, 'error', false, 500), 500);
            return redirect()->back()->with([
                'message'    => $e,
                'alert-type' => 'error',
            ]);
        }
    }
    
}
