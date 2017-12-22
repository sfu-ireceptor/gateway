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
						<th>Duration</th>
						<th>User</th>
					</tr>
				</thead>
				<tbody>
					@foreach ($query_log_list as $t)
						<tr>
							<td class="text-nowrap">{{ human_date_time($t->start_time) }}</td>
							<td>
								<a href="{{ $t->url }} title="{{ $t->url }}">{{ str_limit($t->url, $limit = 64, $end = '‥') }}</a>
							</td>
							<td class="{{ $t->status == 'running' ? 'warning' : ''}}{{ $t->status == 'error' ? 'danger' : ''}}" title='{{ $t->message }}'>
								@if ($t->status == 'done')
									{{ $t->duration <= 5 ? '' : secondsToTime($t->duration) }}
								@elseif ($t->status == 'running')
									{{ $t->status }}
									({{ secondsToTime($t->start_time->diffInSeconds(Carbon\Carbon::now())) }})
								@else
									{{ $t->status }}
									{{ $t->duration <= 5 ? '' : secondsToTime($t->duration) }}
								@endif
							</td>
							<td>{{ $t->username }}</td>
						</tr>
					@endforeach
				</tbody>
			</table>

		</div>
	</div>
</div>
@stop

