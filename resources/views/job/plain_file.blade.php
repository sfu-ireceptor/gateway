@extends('template')

@section('title', $title . ' (Job ' . $job->id . ') ' . $job->agave_status)

@section('base_title', ' ')
 
@section('content')
<div class="container job_container" data-job-id="{{ $job->id }}" data-job-status="{{ $job->status }}">
    <h2>
	{{ $job->app . ': ' . $title }} (Job {{ $job->id }})
    </h2>

<pre>
{{$plain_file}}
</pre>
</div>
@stop
