@extends('template')

@section('title', $job->agave_status)
@section('base_title', ' ')
 
@section('content')
<div class="container job_container" data-job-id="{{ $job->id }}" data-job-status="{{ $job->status }}">

	<h2>
		{{ $job->app }} (Job {{ $job->id }})
                <br />
		<small data-toggle="tooltip" data-placement="right" title="Submitted on {{ $job->createdAtFull() }}"> 
			<span class="submission_date_relative">
				Submitted: {{ $job->createdAtRelative() }}
			</span>
		</small>
                <br />
		<small> 
			<span class="run_time">
				Run time: {{ $job->totalTime() }}
			</span>
		</small>
	</h2>

	<div class="job_view_progress">
	    @include('job/progress')
	    @if ($job->url)
		<p>
			Data from:
			<a href="{{ $job->url }}">
				{{ $job->url }}
			</a>
		</p>
	    @endif
	</div>	

        @if (count($summary) > 0)
            <h2>Summary</h2>
            @foreach ($summary as $summary_line)
		{!! $summary_line !!}<br />
            @endforeach
	@endif

	@if (count($files) > 0 && $job->app != 'Third-party analysis')
            <h2>Files</h2>
	    <div class="result_files">
		{!! $filesHTML !!}
	    </div>
        @endif

	@if (count($files) > 0 && $job->app == 'Third-party analysis')
		<h2>BRepertoire</h2>
		<ul>
			@foreach ($files as $f)
				@if (basename($f) != 'info.txt')
					<li>
						<a href="http://mabra.biomed.kcl.ac.uk/BRepertoire/?branch=analysis&amp;tab=tab_propertyUpload&amp;delim=tab&amp;loadURL={{ config('app.url') . '/' . $f }}" class="btn btn-default" target="_blank">
							{{ basename($f) }}
							<span class="glyphicon glyphicon-export" aria-hidden="true"></span>
						</a>
					</li>
				@endif
			@endforeach
		</ul>
		
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
