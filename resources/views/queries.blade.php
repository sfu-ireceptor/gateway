@extends('template')

@section('title', 'User Queries')

@section('content')
<div class="container">
	<h1>User Queries</h1>

	<div class="row">
		<div class="col-md-12">

			<table class="table table-bordered table-striped">
				<thead>
					<tr>
						<th>Start</th>
						<th>URL</th>
						<th>Status</th>
						<th>Duration</th>
						
					</tr>
				</thead>
				<tbody>
					@foreach ($query_log_list as $t)
						<tr>
							<td>{{ human_date_time($t->start_time) }}</td>
							<td><a href="{{ $t->url }}">{{ $t->url }}</a></td>
							<td>{{ $t->status }}</td>
							<td>{{ $t->duration <= 5 ? '' : secondsToTime($t->duration) }}</td>
						</tr>
					@endforeach
				</tbody>
			</table>

		</div>
	</div>
</div>
@stop

