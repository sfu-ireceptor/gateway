{{ $job->agave_status }}

<div class="progress">
	<div 	class="	progress-bar
				  	{{ $job->status <  2 ? 'progress-bar-striped' 	: ''}}
				  	{{ $job->status == 1 ? 'progress-bar-warning' 			: ''}}
				  	{{ $job->status == 2 ? 'progress-bar-success' 			: ''}}
				  	{{ $job->status == 3 ? 'progress-bar-danger' 			: ''}}
			"
			role="progressbar"
			aria-valuenow="{{ $job->progress }}"
			aria-valuemin="0"
			aria-valuemax="100"
			style="width: {{ $job->progress }}%"
	>
		<span class="sr-only">{{ $job->progress }}%</span>
	</div>
</div>
