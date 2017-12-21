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
						<th>Start Time</th>
						<th>End Time</th>
						<th></th>
						<th></th>
					</tr>
				</thead>
				<tbody>
					@foreach ($query_log_list as $t)
						<tr>
							<td>{{ Carbon\Carbon::parse($t->start_time)->format('M j H:i:s') }}</td>
							<td>{{ Carbon\Carbon::parse($t->end_time)->format('M j H:i:s') }}</td>
						</tr>
					@endforeach
				</tbody>
			</table>

		</div>
	</div>
</div>
@stop

