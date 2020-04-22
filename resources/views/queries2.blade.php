@extends('template')

@section('title', 'User Queries 2')

@section('content')
<div class="container-fluid">
	<h1>User Queries <small>From metadata and sequences pages</small></h1>

	<div class="row">
		<div class="col-md-12">

			<table class="table table-bordered table-striped table-condensed">
				<thead>
					<tr>
						<th>Start</th>
						<th>URL</th>
						<th>Parameters</th>
						<th>Type</th>
						<th>Result Size</th>
						<th>Duration</th>
						<th>User</th>
					</tr>
				</thead>
				<tbody>
					@foreach ($queries as $q)
						<tr>
							<td class="text-nowrap">
								<span class="minor">{{ human_date_time($q->start_time, 'D') }}</span>
								{{ human_date_time($q->start_time, 'M j') }}
								<span class="minor">{{ human_date_time($q->start_time, 'H:i') }}</span>
							</td>
							<td>
								<a href="{{ $q->url}}" title="{{ $q->url }}">
									{{ str_limit(url_path($q->url), $limit = 70, $end = '‥') }}
								</a>
							</td>
							<td>
								@if(isset($q->params) && ! empty($q->params))
									<p><code>{{ json_encode($q->params, JSON_PRETTY_PRINT) }}</code></p>
								@endif
							</td>
							<td class="text-nowrap">
								{{ $q->type }}
								@isset($q->file)
									({{ $q->file }})
								@endisset
							</td>
							<td>
								@if ($q->file)
									{{ human_filesize($q->result_size) }}
								@else
									{{ number_format($q->result_size) }}
								@endif								
							</td>
							<td class="{{ $q->status == 'running' ? 'warning' : ''}}{{ $q->status == 'error' ? 'danger' : ''}}{{ $q->status == 'service_error' ? 'danger' : ''}}" title='{{ $q->message }}'>
								@if ($q->status == 'done')
									<span class="{{ $q->duration <= 5 ? 'minor2' : ''}}">{{ secondsToTime($q->duration) }}</span>
								@elseif ($q->status == 'running')
									{{ $q->status }}
									({{ secondsToTime($q->start_time->diffInSeconds(Carbon\Carbon::now())) }})
								@elseif ($q->status == 'service_error')
									<span class="{{ $q->duration <= 5 ? 'minor2' : ''}}">{{ secondsToTime($q->duration) }}</span>
									(with service error)
								@else
									{{ $q->status }}
									<span class="{{ $q->duration <= 5 ? 'minor2' : ''}}">{{ secondsToTime($q->duration) }}</span>
								@endif
							</td>
							<td>{{ hash('adler32', $q->username) }}</td>
						</tr>
					@endforeach
				</tbody>
			</table>

		</div>
	</div>
</div>
@stop

