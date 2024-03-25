@extends('template')

@section('title', $job->app . ' (Job ' . $job->id . ') ' . $job->agave_status)
@section('base_title', ' ')

@section('content')
<div class="container job_container">
    @if (count($plain_file) > 0)
        @foreach ($plain_file as $line)
        <!--
            {!! preg_replace($pattern, $replace_str, $line) !!}
        -->
            {!! $line !!}
        @endforeach
    @else
	File is empty
    @endif
</div>
@stop
