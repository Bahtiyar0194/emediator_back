
@extends('layouts.layout')

@section('document')
    <ol style="padding-left: 0px">
        @yield('agreement_content')

        @foreach($points as $key => $point)
            <li>
                {!! $point->content !!}
            </li>
        @endforeach
    </ol>
@endsection
