@foreach ($job_list_grouped_by_month as $month => $job_list)
<h2>{{ $month }}</h2>
	<table class="table table-striped jobs">

		<tbody>
			@foreach ($job_list as $job)
			<tr>
				<td>
					<a href="jobs/view/{{ $job->id }}">
						{{ $job->createdAt() }}
					</a><br />
					<em class="dateRelative">{{ $job->createdAtRelative() }}</em>
				</td>

				<td class="status">						
					@include('jobProgress')
				</td>
				<td>
					<a href="/jobs/delete/{{ $job->id }}">
						<button type="button" class="btn btn-default" aria-label="Delete">
						  <span class="glyphicon glyphicon-trash" aria-hidden="true"></span> Delete
						</button>
					</a>
				</td>
			<tr>
			@endforeach
		</tbody>
	</table>
@endforeach
