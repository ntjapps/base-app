@extends('base-components.base')
@section('title', 'User Management')

@section('body')
    @parent
    <router-view
    app-name="{{ config('app.name') }}"
    greetings="{{ Auth::user()?->name }}"
    ></router-view>
@endsection