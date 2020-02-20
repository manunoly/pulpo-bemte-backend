@extends('voyager::master')

@section('page_title', 'Marketing')

@section('page_header')
    <div class="container-fluid">
        <h1 class="page-title">
            <i class="voyager-tv"></i> Marketing
        </h1>
        @include('voyager::multilingual.language-selector')
    </div>
@stop


@section('content')
    <div class="page-content browse container-fluid">

@if(session('success'))
<span class="label label-success">{{ session('success') }}</span>
@endif
@if(session('error'))
<span class="label label-danger">{{ session('error') }}</span>
@endif
        <div class="row">
            <div class="col-md-12">
            <form role="form"
                            class="form-edit-add"
                            action="{{ route('marketing.enviar', 'user_id=1') }}"
                            method="GET" enctype="multipart/form-data">
                <div class="panel panel-bordered">
                        <div class="panel-body">
                            <div class="form-group col-md-3">
                                <label class="control-label" for="name">Profesores</label>
                                <input type="checkbox" name="profesor">
                            </div>
                            <div class="form-group col-md-3">
                                <label class="control-label" for="name">Alumnos</label>
                                <input type="checkbox" name="alumno">
                            </div>
                            <div class="form-group col-md-8">
                            </div>
                            <div class="form-group col-md-6">
                                <label class="control-label" for="name">Ciudad</label>
                                <select class="form-control select2" name="ciudad">
                                @foreach($ciudades as $ciudad)
                                    <option value="{{$ciudad->ciudad}}">{{$ciudad->ciudad }}
                                    ({{ $ciudad->pais }})</option>
                                @endforeach
                                </select>
                            </div>
                            <div class="form-group col-md-12">
                                <textarea name="mensaje" cols="80" rows="4" 
                                    placeholder="Mensaje a Enviar" maxlength="250"></textarea>
                            </div>
                        </div>
                        <div class="panel-footer">
                            <button type="submit" class="btn btn-primary save">Enviar Mensaje</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@stop

@section('css')
@if(config('dashboard.data_tables.responsive'))
    <link rel="stylesheet" href="{{ voyager_asset('lib/css/responsive.dataTables.min.css') }}">
@endif
@stop

@section('javascript')
    <!-- DataTables -->
    @if(config('dashboard.data_tables.responsive'))
        <script src="{{ voyager_asset('lib/js/dataTables.responsive.min.js') }}"></script>
    @endif
    <script>
        $(document).ready(function () {
            
        });
    </script>
@stop
