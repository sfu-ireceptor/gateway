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
					@foreach ($queries as $q)
						<tr>
							<td class="text-nowrap">
								<a href="/admin/queries/{{ $q->id }}">{{ human_date_time($q->start_time) }}</a>
							</td>
							<td>
								<a href="{{ $q->url }} title="{{ $q->url }}">{{ str_limit($q->url, $limit = 64, $end = '‥') }}</a>
							</td>
							<td class="{{ $q->status == 'running' ? 'warning' : ''}}{{ $q->status == 'error' ? 'danger' : ''}}" title='{{ $q->message }}'>
								@if ($q->status == 'done')
									{{ $q->duration <= 5 ? '' : secondsToTime($q->duration) }}
								@elseif ($q->status == 'running')
									{{ $q->status }}
									({{ secondsToTime($q->start_time->diffInSeconds(Carbon\Carbon::now())) }})
								@else
									{{ $q->status }}
									{{ $q->duration <= 5 ? '' : secondsToTime($q->duration) }}
								@endif
							</td>
							<td>{{ $q->username }}</td>
						</tr>
					@endforeach
				</tbody>
			</table>

		</div>
	</div>
</div>
@stop

