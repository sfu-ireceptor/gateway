@extends('template')

@section('title', $job->app . ' (Job ' . $job->id . ') ' . $job->agave_status)
@section('base_title', ' ')
<!--
@section('base', asset('jobs/view/file/'.$job->id.'/'))
-->
@section('base', asset('/storage/Total/'))

@section('content')
<div class="container job_container">
{{ asset('jobs/view/file/'.$job->id.'?file=')}}
    @if (count($plain_file) > 0)
        @foreach ($plain_file as $line)
	    {!! $line !!}
        @endforeach
    @else
	File is empty
    @endif
</div>
@stop
