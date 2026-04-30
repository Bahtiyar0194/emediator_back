
@extends('layouts.layout')

@section('document')
    <ol style="padding-left: 0px">
        <x-point-item :points="$data" />
    </ol>
@endsection
