@extends('voyager::master')

@section('page_title', __('voyager::generic.view').' '.$dataType->display_name_singular)

@section('page_header')

    <h1 class="page-title">
        <i class="{{ $dataType->icon }}"></i> {{ __('voyager::generic.viewing') }} {{ ucfirst($dataType->display_name_singular) }} &nbsp;

        @can('edit', $dataTypeContent)
            <a href="{{ route('voyager.'.$dataType->slug.'.edit', $dataTypeContent->getKey()) }}" class="btn btn-info">
                <span class="glyphicon glyphicon-pencil"></span>&nbsp;
                {{ __('voyager::generic.edit') }}
            </a>
        @endcan
        @can('delete', $dataTypeContent)
            @if($isSoftDeleted)
                <a href="{{ route('voyager.'.$dataType->slug.'.restore', $dataTypeContent->getKey()) }}" title="{{ __('voyager::generic.restore') }}" class="btn btn-default restore" data-id="{{ $dataTypeContent->getKey() }}" id="restore-{{ $dataTypeContent->getKey() }}">
                    <i class="voyager-trash"></i> <span class="hidden-xs hidden-sm">{{ __('voyager::generic.restore') }}</span>
                </a>
            @else
                <a href="javascript:;" title="{{ __('voyager::generic.delete') }}" class="btn btn-danger delete" data-id="{{ $dataTypeContent->getKey() }}" id="delete-{{ $dataTypeContent->getKey() }}">
                    <i class="voyager-trash"></i> <span class="hidden-xs hidden-sm">{{ __('voyager::generic.delete') }}</span>
                </a>
            @endif
        @endcan

        <a href="{{ route('voyager.'.$dataType->slug.'.index') }}" class="btn btn-warning">
            <span class="glyphicon glyphicon-list"></span>&nbsp;
            {{ __('voyager::generic.return_to_list') }}
        </a>
        
        @if (Auth::user()->tipo == 'Administrador')
            <a href="javascript:;" class="btn btn-default" data-toggle="modal" id="pagarButton" data-target="#pagar_modal"> <i class="fas fa-plus-circle"></i>
            <i class="voyager-dollar pull-left"></i><span>  Pagar  </span>
            </a>
    
            <a href="javascript:;" data-toggle="modal" id="multarButton" data-target="#multar_modal" class="btn btn-dark">
            <i class="voyager-camera pull-left"></i><span>  Multar  </span>
            </a>
        @endif
    </h1>
    @include('voyager::multilingual.language-selector')
@stop

@section('content')
    <div class="page-content read container-fluid">

@if(session('success'))
<span class="label label-success">{{ session('success') }}</span>
@endif
@if(session('error'))
<span class="label label-danger">{{ session('error') }}</span>
@endif

        <div class="row">
            <div class="col-md-12">

                <div class="panel panel-bordered" style="padding-bottom:10px;">
                <h3 class="panel-title">Cálculo del valor total pagos: </h3>
                    <form role="form"
                            class="form-edit-add"
                            action="{{ route('profesor.updateOrCreate', 'user_id='.$dataTypeContent->getKey()) }}"
                            method="POST" enctype="multipart/form-data">
                        {{ method_field('PUT') }}
                        {{ csrf_field() }}
                        <div class="panel-body">
                            <div class="column" style="padding-top:5px;">
                                <div class='col-sm-4' style="padding-top:5px;">
                                    <input id="startDate" class="date form-control"type="text" name="input" placeholder="Fecha Inicio" onchange="myFunction();">

                                </div>
                                
                                <div class='col-sm-4' style="padding-top:5px;">
                                    <input id="endDate" class="date form-control"type="text" name="input" placeholder="Fecha Fin" onchange="mainInfo(this.value);">
                                </div>

                                <div style="padding-bottom:5px;">
                                <a href="javascript:;" class="btn btn-default" id="valorTotalButton" data-target="#valorTotal_modal" >
                                    <i class="voyager-dollar pull-left"></i><span>  Calcular  </span>
                                </a>
                                </div>
                            </div>
                            
                        </div>
                    </form>
                    <!-- form start -->
                    @foreach($dataType->readRows as $row)
                        @php
                        if ($dataTypeContent->{$row->field.'_read'}) {
                            $dataTypeContent->{$row->field} = $dataTypeContent->{$row->field.'_read'};
                        }
                        @endphp
                        <div class="panel-heading" style="border-bottom:0;">
                            <h3 class="panel-title">{{ $row->display_name }}</h3>
                        </div>

                        <div class="panel-body" style="padding-top:0;">
                            @if (isset($row->details->view))
                                @include($row->details->view, ['row' => $row, 'dataType' => $dataType, 'dataTypeContent' => $dataTypeContent, 'content' => $dataTypeContent->{$row->field}, 'action' => 'read'])
                            @elseif($row->type == "image")
                                <img class="img-responsive"
                                     src="{{ filter_var($dataTypeContent->{$row->field}, FILTER_VALIDATE_URL) ? $dataTypeContent->{$row->field} : Voyager::image($dataTypeContent->{$row->field}) }}">
                            @elseif($row->type == 'multiple_images')
                                @if(json_decode($dataTypeContent->{$row->field}))
                                    @foreach(json_decode($dataTypeContent->{$row->field}) as $file)
                                        <img class="img-responsive"
                                             src="{{ filter_var($file, FILTER_VALIDATE_URL) ? $file : Voyager::image($file) }}">
                                    @endforeach
                                @else
                                    <img class="img-responsive"
                                         src="{{ filter_var($dataTypeContent->{$row->field}, FILTER_VALIDATE_URL) ? $dataTypeContent->{$row->field} : Voyager::image($dataTypeContent->{$row->field}) }}">
                                @endif
                            @elseif($row->type == 'relationship')
                                 @include('voyager::formfields.relationship', ['view' => 'read', 'options' => $row->details])
                            @elseif($row->type == 'select_dropdown' && property_exists($row->details, 'options') &&
                                    !empty($row->details->options->{$dataTypeContent->{$row->field}})
                            )
                                <?php echo $row->details->options->{$dataTypeContent->{$row->field}};?>
                            @elseif($row->type == 'select_multiple')
                                @if(property_exists($row->details, 'relationship'))

                                    @foreach(json_decode($dataTypeContent->{$row->field}) as $item)
                                        {{ $item->{$row->field}  }}
                                    @endforeach

                                @elseif(property_exists($row->details, 'options'))
                                    @if (!empty(json_decode($dataTypeContent->{$row->field})))
                                        @foreach(json_decode($dataTypeContent->{$row->field}) as $item)
                                            @if (@$row->details->options->{$item})
                                                {{ $row->details->options->{$item} . (!$loop->last ? ', ' : '') }}
                                            @endif
                                        @endforeach
                                    @else
                                        {{ __('voyager::generic.none') }}
                                    @endif
                                @endif
                            @elseif($row->type == 'date' || $row->type == 'timestamp')
                                {{ property_exists($row->details, 'format') ? \Carbon\Carbon::parse($dataTypeContent->{$row->field})->formatLocalized($row->details->format) : $dataTypeContent->{$row->field} }}
                            @elseif($row->type == 'checkbox')
                                @if(property_exists($row->details, 'on') && property_exists($row->details, 'off'))
                                    @if($dataTypeContent->{$row->field})
                                    <span class="label label-info">{{ $row->details->on }}</span>
                                    @else
                                    <span class="label label-primary">{{ $row->details->off }}</span>
                                    @endif
                                @else
                                {{ $dataTypeContent->{$row->field} }}
                                @endif
                            @elseif($row->type == 'color')
                                <span class="badge badge-lg" style="background-color: {{ $dataTypeContent->{$row->field} }}">{{ $dataTypeContent->{$row->field} }}</span>
                            @elseif($row->type == 'coordinates')
                                @include('voyager::partials.coordinates')
                            @elseif($row->type == 'rich_text_box')
                                @include('voyager::multilingual.input-hidden-bread-read')
                                <p>{!! $dataTypeContent->{$row->field} !!}</p>
                            @elseif($row->type == 'file')
                                @if(json_decode($dataTypeContent->{$row->field}))
                                    @foreach(json_decode($dataTypeContent->{$row->field}) as $file)
                                        <a href="{{ Storage::disk(config('voyager.storage.disk'))->url($file->download_link) ?: '' }}">
                                            {{ $file->original_name ?: '' }}
                                        </a>
                                        <br/>
                                    @endforeach
                                @else
                                    <a href="{{ Storage::disk(config('voyager.storage.disk'))->url($row->field) ?: '' }}">
                                        {{ __('voyager::generic.download') }}
                                    </a>
                                @endif
                            @else
                                @include('voyager::multilingual.input-hidden-bread-read')
                                <p>{{ $dataTypeContent->{$row->field} }}</p>
                            @endif
                        </div><!-- panel-body -->
                        @if(!$loop->last)
                            <hr style="margin:0;">
                        @endif
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- Single delete modal --}}
    <div class="modal modal-danger fade" tabindex="-1" id="delete_modal" role="dialog">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="{{ __('voyager::generic.close') }}"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><i class="voyager-trash"></i> {{ __('voyager::generic.delete_question') }} {{ strtolower($dataType->display_name_singular) }}?</h4>
                </div>
                <div class="modal-footer">
                    <form action="{{ route('voyager.'.$dataType->slug.'.index') }}" id="delete_form" method="POST">
                        {{ method_field('DELETE') }}
                        {{ csrf_field() }}
                        <input type="submit" class="btn btn-danger pull-right delete-confirm"
                               value="{{ __('voyager::generic.delete_confirm') }} {{ strtolower($dataType->display_name_singular) }}">
                    </form>
                    <button type="button" class="btn btn-default pull-right" data-dismiss="modal">{{ __('voyager::generic.cancel') }}</button>
                </div>
            </div><!-- /.modal-content -->
        </div><!-- /.modal-dialog -->
    </div><!-- /.modal -->

    {{-- Single pagar modal --}}
    <div class="modal modal-success fade" id="pagar_modal" tabindex="1" role="dialog" aria-labelledby="mediumModalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title"><i class="voyager-dollar"></i> ¿Estás seguro que deseas realizarlo?</h4>
                </div>
                <div class="modal-footer">
                    <a href="{{ route('profesor.pagar', 'user_id='.$dataTypeContent->getKey()) }}"  class="btn btn-warning pull-right">
                        <span> Si </span>
                    </a>
                                
                    <button type="button" class="btn btn-default pull-right" data-dismiss="modal"> No </button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="valorTotal_modal" tabindex="-1" role="dialog" aria-labelledby="mediumModalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form role="form"
                            class="form-edit-add"
                            action="{{ route('profesor.updateOrCreate', 'user_id='.$dataTypeContent->getKey()) }}"
                            method="POST" enctype="multipart/form-data">
                        {{ method_field('PUT') }}
                        {{ csrf_field() }}
                        <h3 class="panel-title">Desde: </h3> 
                        <div class="panel-body">
                            <div class="row">
                                <div class='col-sm-6'>
                                    <input id="startDate" class="date form-control"type="text" name="input" placeholder="mm-dd-yyyy" onchange="myFunction();">

                                </div>
                            </div>
                        </div>
                        <h3 class="panel-title">Hasta: </h3>
                        <div class="panel-body"> 
                            <div class="row">
                                <div class='col-sm-6'>
                                    <input id="endDate" class="date form-control"type="text" name="input" placeholder="mm-dd-yyyy" onchange="mainInfo(this.value);">
                                </div>
                            </div>
                            <!-- <input id="endtdate" class="date form-control"type="text" name="input" placeholder="dd-mm-yyyy" required pattern="(?:30))|(?:(?:0[13578]|1[02])-31))/(?:0[1-9]|1[0-9]|2[0-9])|(?:(?!02)(?:0[1-9]|1[0-2])/(?:19|20)[0-9]{2}-(?:(?:0[1-9]|1[0-2])"> -->
                            <!-- <input class="date form-control"  type="text" id="enddate" name="enddate">  -->
                        </div>
                        <!-- <div class="panel-body" style="padding-top:0;">
                            <a href="{{ route('profesor.updateOrCreate') }}" class="btn btn-default" id="valorTotalButton" data-target="#valorTotal_modal" >
                                <i class="voyager-dollar pull-left"></i><span>  Calcular  </span>
                            </a>
                        </div> -->
                    
                <div class="modal-footer">
                    <a href="{{ route('profesor.pagar', 'user_id='.$dataTypeContent->getKey()) }}"  class="btn btn-warning pull-right">
                    <!-- <a href="{{ route('profesor.updateOrCreate', 'user_id='.$dataTypeContent->getKey() ) }}" class="btn btn-warning pull-right" data-toggle="modal" id="pagarButton" data-target="#pagar_modal" data-dismiss="modal"> <i class="fas fa-plus-circle"></i> -->
                    <!-- <a href="{{ route('profesor.pagar', 'user_id='.$dataTypeContent->getKey()) }}"  class="btn btn-warning pull-right"> -->
                        <span> Pagar </span>
                    </a>
                                
                    <button type="button" class="btn btn-default pull-right" data-dismiss="modal"> Salir </button>
                </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Single multar modal --}}
    <div class="modal modal-success fade" id="multar_modal" tabindex="-1" role="dialog" aria-labelledby="mediumModalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title"><i class="voyager-dollar"></i> ¿Estás seguro que deseas realizarlo?</h4>
                </div>
                <div class="modal-footer">
                    <a href="{{ route('profesor.multar', 'user_id='.$dataTypeContent->getKey()) }}"  class="btn btn-warning pull-right">
                        <span> Si </span>
                    </a>
                                
                    <button type="button" class="btn btn-default pull-right" data-dismiss="modal"> No </button>
                </div>
            </div>
        </div>
    </div>
@stop

@section('javascript')
    @if ($isModelTranslatable)
        <script>
            $(document).ready(function () {
                $('.side-body').multilingual();
            });
        </script>
        <script src="{{ voyager_asset('js/multilingual.js') }}"></script>
    @endif
    <script>
        var deleteFormAction;
        $('.delete').on('click', function (e) {
            var form = $('#delete_form')[0];

            if (!deleteFormAction) {
                // Save form action initial value
                deleteFormAction = form.action;
            }

            form.action = deleteFormAction.match(/\/[0-9]+$/)
                ? deleteFormAction.replace(/([0-9]+$)/, $(this).data('id'))
                : deleteFormAction + '/' + $(this).data('id');

            $('#delete_modal').modal('show');
        });

    </script>

    <script>
        var pagarFormAction;
        $('.pagar').on('click','pagar', function (e) {
            var form = $('#pagar_form')[0];

            if (!pagarFormAction) {
                // Save form action initial value
                pagarFormAction = form.action;
            }

            form.action = pagarFormAction.match(/\/[0-9]+$/)
                ? pagarFormAction.replace(/([0-9]+$)/, $(this).data('id'))
                : pagarFormAction + '/' + $(this).data('id');

            $('#pagar_modal').modal('show');
        });

    </script>

<script>
        var pagarFormAction;
        $('.valorTotal').on('click','pagar', function (e) {
            var form = $('#pagar_form')[0];

            if (!pagarFormAction) {
                // Save form action initial value
                pagarFormAction = form.action;
            }

            form.action = pagarFormAction.match(/\/[0-9]+$/)
                ? pagarFormAction.replace(/([0-9]+$)/, $(this).data('id'))
                : pagarFormAction + '/' + $(this).data('id');

            $('#valorTotal_modal').modal('show');
        });

    </script>

    <script>
        var multarFormAction;
        $('.multar').on('click','multar', function (e) {
            var form = $('#multar_form')[0];

            if (!multarFormAction) {
                // Save form action initial value
                multarFormAction = form.action;
            }

            // form.action = pagarFormAction.match(/\/[0-9]+$/)
            //     ? pagarFormAction.replace(/([0-9]+$)/, $(this).data('id'))
            //     : pagarFormAction + '/' + $(this).data('id');

            $('#multar_modal').modal('show');
        });

    </script>

    <script>
        function myFunction() {
        var x = document.getElementById("startDate").value;
        console.log('x', x);
        document.getElementById("demo").innerHTML = "You selected: " + x;
        }
    </script>

    <script type="text/javascript">
        $(function () {
            $('#startDate').each(function (idx, elt) {
                console.log('elt', elt.type);
                console.log('elt', elt.value);
                if (elt.type != 'date' || elt.hasAttribute('data-datepicker')) {
                    elt.type = 'text';
                    $(elt).datetimepicker($(elt).data('datepicker'));
                    $startDate = $(elt).data('datepicker');
                }

                function mainInfo(date) {
                // $.ajax({
                //     method: 'GET',
                //     data: "mainid =" + date,
                //     success: function(result) {
                //         $("#somewhere").html(result);
                //     }
                // });
                $startDate = date;
                };
            });
            $('#endDate').each(function (idx, elt) {
                if (elt.type != 'date' || elt.hasAttribute('data-datepicker')) {
                    elt.type = 'text';
                    $(elt).datetimepicker($(elt).data('datepicker'));
                    $endDate = $(elt).data('datepicker');
                }
                function mainInfo(date) {
                $endDate = date;
                    
                };
                
            });

            $('#valorTotalButton').on('click', function (e) {
                $.ajax({
                        url: "{{ route('profesor.updateOrCreate') }}",
                        method: 'POST',
                        data: {
                            'user_id' : '{{ $dataTypeContent->getKey() }}',
                            'startDate': document.getElementById("startDate").value,
                            'endDate': document.getElementById("endDate").value,
                        },
                        success: function (data) {
                            console.log('success');
                            console.log('startDate', startDate);
                            console.log('endDate', endDate);
                            // console.log('data', data);
                            // var href = window.location.href;
                            // console.log(window.location.href)
                            // console.log(href.split('#').length)
                            // top.location.href = '/bopriceApi/public/admin/internetfijo/274/edit#extrasTab';
                            location.reload();
                        }
                    });
                });           
        }); 
    </script>
    
@stop
