@extends('template')

@section('title', 'Query ' . $query_id)

@section('content')
<div class="container">
	<h1>Query {{ $query_id }}</h1>

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
			<tr>
				<td class="text-nowrap">
					{{ human_date_time($q->start_time) }}
				</td>
				<td>
					<a href="{{ $q->url }} title="{{ $q->url }}">{{ str_limit($q->url, $limit = 64, $end = '‥') }}</a>

					@if(isset($q->params) && ! empty($q->params))
						<!-- Button trigger modal -->
						<button type="button" class="btn btn-default btn-xs" data-toggle="modal" data-target="#myModal_{{ $q->id }}">
						  Filters
						</button>

						<!-- Modal -->
						<div class="modal fade" id="myModal_{{ $q->id }}" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
						  <div class="modal-dialog" role="document">
						    <div class="modal-content">
						      <div class="modal-header">
						        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
						        <h4 class="modal-title" id="myModalLabel">Filters for {{ $q->url }}</h4>
						      </div>
						      <div class="modal-body">
						      	<pre>{{ json_encode($q->params, JSON_PRETTY_PRINT) }}</pre>
						      </div>
						      <div class="modal-footer">
						        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
						      </div>
						    </div>
						  </div>
						</div>
					@endif
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
		</tbody>
	</table>

	<h3>Service queries</h3>

	<div class="row">
		<div class="col-md-12">

			<table class="table table-bordered table-striped">
				<thead>
					<tr>
						<th>Service</th>
						<th>Start</th>
						<th>URL</th>
						<th>Duration</th>
					</tr>
				</thead>
				<tbody>
					@foreach ($node_queries as $q)
						<tr>
							<td class="text-nowrap">
								{{ $q->rest_service_name}}
							</td>
							<td class="text-nowrap">
								{{ human_date_time($q->start_time) }}
							</td>
							<td>

								{{ str_limit($q->url, $limit = 84, $end = '‥') }}
								
								<!-- Button trigger modal -->
								<button type="button" class="btn btn-default btn-xs" data-toggle="modal" data-target="#myModal_{{ $q->id }}">
								  POST parameters
								</button>

								<!-- Modal -->
								<div class="modal fade" id="myModal_{{ $q->id }}" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
								  <div class="modal-dialog" role="document">
								    <div class="modal-content">
								      <div class="modal-header">
								        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
								        <h4 class="modal-title" id="myModalLabel">POST parameters ({{ $q->url }} on {{ $q->rest_service_name}})</h4>
								      </div>
								      <div class="modal-body">
								      	<pre>{{ json_encode($q->params, JSON_PRETTY_PRINT) }}</pre>
								      </div>
								      <div class="modal-footer">
								        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
								      </div>
								    </div>
								  </div>
								</div>

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
						</tr>
					@endforeach
				</tbody>
			</table>

		</div>
	</div>
</div>
@stop

