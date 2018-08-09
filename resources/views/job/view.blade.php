@extends('template')

@section('title', $job->agave_status)
@section('base_title', ' ')
 
@section('content')
<div class="container job_container" data-job-id="{{ $job->id }}" data-job-status="{{ $job->status }}">

	<h1>
		Job {{ $job->id }}
		<small data-toggle="tooltip" data-placement="right" title="Submitted on {{ $job->createdAtFull() }}"> 
			<span class="submission_date_relative">
				{{ $job->createdAtRelative() }}
			</span>
		</small>
	</h1>

	<div class="job_view_progress">
		@include('job/progress')
	</div>	

	<h2>{{ $job->app }}</h2>
	@if ($job->url)
		<p>
			Data from:
			<a href="{{ $job->url }}">
				{{ $job->url }}
			</a>
		</p>
	@endif


	@if (count($files) > 0)
		<h2>Files</h2>
		<div class="result_files">
			{{ $filesHTML }}
		</div>
	@endif

	<div class="job_steps">
		@include('job/steps')
	</div>


	<p>
		<a href="/jobs/delete/{{ $job->id }}">
			<button type="button" class="btn btn-default" aria-label="Delete">
			  <span class="glyphicon glyphicon-trash" aria-hidden="true"></span> Delete this job
			</button>
		</a>
	</p>


<!-- 
	jobs-history -V {{ $job->agave_id }}
 -->

</div>
 @stop