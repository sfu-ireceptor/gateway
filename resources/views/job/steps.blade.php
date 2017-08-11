@if (count($steps) > 0)
	<h2>History</h2>

	@if (isset($job))
		<p>
			Total duration: {{ $job->totalTime() }}
		</p>	
	@endif

	<table class="table table-bordered table-striped">
		<thead>
			<tr>
				<th>Status</th>
				<th>Duration</th>
				<th>Started</th>
			</tr>
		</thead>
		<tbody>
			@foreach ($steps as $step)
				<tr>
					<td>{{ $step->statusHuman() }}</td>
					<td>
						@if ($step->durationSeconds() <= 60)
							<span class="text-muted">{{ $step->durationHuman() }}</span>
						@else
							{{ $step->durationHuman() }}
						@endif
					</td>
					<td>{{ $step->createdAt() }}</td>
				</tr>
			@endforeach
		</tbody>
	</table>

@if (isset($job) && $job->progress >= 20)
	<p>
		<a href="/jobs/agave-history/{{ $job->id }}">View Agave history</a>
	</p>	
@endif

@endif