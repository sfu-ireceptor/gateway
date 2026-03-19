{{-- resources/views/errors/403.blade.php --}}
{{--
--}}
@extends('template')

@section('content')
<div class="banner_title samples">
    <h1>Access Control Error</h1>
    <p>{{ $exception->getMessage() ?: 'Sorry, you are forbidden from accessing this page.' }}</p>
@endsection
