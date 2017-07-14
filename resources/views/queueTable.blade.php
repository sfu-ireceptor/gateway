<table class="table table-bordered table-striped">
	<thead>
		<tr>
			<th>Status</th>
			<th>Description</th>
			<th>Submitted</th>
			<th>Start</th>
			<th>End</th>
			<th>User</th>
		</tr>
	</thead>
	<tbody>
		@foreach ($jobList as $j)
			<tr class="text-nowprap">
				<td>
					{{ $j->status }}
						<div class="progress local-job-progress">
						<div class="	progress-bar
									  	{{ $j->status ==  'Pending' ? 'progress-bar-striped' 	: ''}}
									  	{{ $j->status == 'Running' ? 'progress-bar-warning' 			: ''}}
									  	{{ $j->status == 'Finished' ? 'progress-bar-success' 			: ''}}
									  	{{ $j->status == 'Failed' ? 'progress-bar-danger' 			: ''}}
									"
													role="progressbar"
													style="width: {{ $j->progress() }}%"
						>
						</div>
					</div>
				</td>
				<td>{{ $j->description }}</td>
				<td>{{ $j->submitted() }}</td>
				<td>{{ $j->start() }}</td>
				<td>{{ $j->end() }}</td>	
				<td>{{ $j->user }}</td>						
			</tr>
		@endforeach
	</tbody>
</table>
