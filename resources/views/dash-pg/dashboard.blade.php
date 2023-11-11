@extends('base-components.base')
@section('title', 'Dashboard')

@section('body')
    @parent
    <router-view page-title="Overview Dashboard" />
@endsection