@extends('template')

@section('title', $job->app . ' (Job ' . $job->id . ') ' . $job->agave_status)
@section('base_title', ' ')

@section('content')
<div class="container job_container">
    @if (count($html_file) > 0)
        @foreach ($html_file as $line)
            {!! $line !!}
        @endforeach
    @else
	File is empty
    @endif
</div>
@stop
