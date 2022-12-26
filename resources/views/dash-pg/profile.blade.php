@extends('base-components.base')
@section('title', 'Profile')

@section('body')
    @parent
    <router-view
    app-name="{{ config('app.name') }}"
    greetings="{{ Auth::user()?->name }}"
    user-name="{{ Auth::user()?->name }}"
    ></router-view>
@endsection