@extends('base-components.base')
@section('title', 'Server Log')

@section('body')
    @parent
    <router-view
    expanded-keys-props="{{ $expandedKeys }}"
    ></router-view>
@endsection