@extends('template')
@section('title', 'Down for Maintenance')

@section('content')
<div class="banner_title samples">
    <h1>The iReceptor Gateway is down for maintenance</h1>
    <p> {!! config('ireceptor.message_503_error') !!}
    </p>
@endsection

