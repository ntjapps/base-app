@extends('base-components.base')
@section('title', 'Login')

@section('body')
    @parent
    <router-view
    app-name="{{ config('app.name') }}"
    ></router-view>
@endsection