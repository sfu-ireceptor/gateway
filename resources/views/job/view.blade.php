@extends('template')

@section('title', $job->agave_status)
@section('base_title', ' ')
 
@section('content')
<div class="container job_container" data-job-id="{{ $job->id }}" data-job-status="{{ $job->status }}">

	<h2>
		{{ $job->app }} (Job {{ $job->id }})
                <br />
		<small data-toggle="tooltip" data-placement="right" title="Submitted on {{ $job->createdAtFull() }}"> 
			Submitted: <span class="submission_date_relative">
				{{ $job->createdAtRelative() }}
			</span>
		</small>
                <br />
		<small> 
			Run time: <span class="run_time">{{ $job->totalTime() }}</span>
		</small>
	</h2>

	<div class="job_view_progress">
	    @include('job/progress')
	</div>	
	@if ($job->url)
		Data from:
                 <span class="job_url">
                    <a href="{{ $job->url }}"> {{ $job->url }} </a>
	        </span>
	@endif

        @if (count($summary) > 0)
            <h2>Data Summary</h2>
            <div class="summary">
            @foreach ($summary as $summary_line)
		{!! $summary_line !!}
            @endforeach
	    </div>
	@endif

        @if (count($job_summary) > 0)
            <h2>Job Summary</h2>
            <div class="summary">
            @foreach ($job_summary as $summary_line)
		{!! $summary_line !!}
            @endforeach
	    </div>
	@endif

        @if (count($error_summary) > 0)
            <h2>Error Summary</h2>
            <div class="summary">
            @foreach ($error_summary as $summary_line)
		{!! $summary_line !!}
            @endforeach
	    </div>
	@endif

	@if ($filesHTML != '' && $job->app != 'Third-party analysis')
            <h2>Analysis Output</h2>
                <div role="tabpanel" class="analysis_apps_tabpanel">
                    <!-- Tab links -->
                    <ul class="nav nav-tabs" role="tablist">
                        <li role="presentation" class="active"><a href="#summary" aria-controls="summary" role="tab" data-toggle="tab">Analysis Summary</a></li>
                        <li role="presentation"><a href="#details" aria-controls="details" role="tab" data-toggle="tab">Analysis Details</a></li>
                    </ul>
                <div class="tab-content">
                    <div role="tabpanel" class="tab-pane active" id="summary">

                        @if (count($analysis_summary) > 0)
                            <div class="summary">
                            </br>
                            @foreach ($analysis_summary as $summary_object)
		            <a role="button" class="btn btn-primary browse_sequences browse-seq-data-button button_to_enable_on_load"  href="{{ $summary_object['url'] }}">
			        Analysis Summary - {{$summary_object['label']}}
                            </a></br></br>
                            @endforeach
	                    </div>
	                @endif
	                @if ($analysis_download_url != '') 
		            <a role="button" class="btn btn-primary browse_sequences browse-seq-data-button button_to_enable_on_load"  href="/{{ $analysis_download_url }}">
        
                                Download Analysis Results Archive (ZIP)
                            </a>
                        @endif
	                @if ($output_log_url != '') 
		            <a role="button" class="btn btn-primary browse_sequences browse-seq-data-button button_to_enable_on_load"  href="/{{ $output_log_url }}">
                                View Analysis Output Log 
                            </a>
                        @endif
	                @if ($error_log_url != '') 
		            <a role="button" class="btn btn-primary browse_sequences browse-seq-data-button button_to_enable_on_load"  href="/{{ $error_log_url }}">
                                View Analysis Error Log
                            </a>
                        @endif
		    </div>
                    <div role="tabpanel" class="tab-pane" id="details">
	                <div class="result_files">
		            {!! $filesHTML !!}
	                </div>
                    </div>
                </div>
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
