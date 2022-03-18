@extends('template')

@section('title', 'Query ' . $query_id)

@section('content')
<div class="container">
	<h1>
		{{ $q->type == 'job' ? 'Download' : 'User Query'}}
	</h1>

	<table class="table table-bordered table-striped">
		<thead>
			<tr>
				<th>Start</th>
				<th>URL</th>
				<th>Type</th>
				<th>Result Size</th>
				<th>Duration</th>
				<th>User</th>
				<th></th>
			</tr>
		</thead>
		<tbody>
			<tr>
				<td class="text-nowrap">
					{{ human_date_time($q->start_time) }}
				</td>
				<td>
					{{ str_limit(url_path($q->url), $limit = 64, $end = '‥') }}

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
				<td>
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
				<td><a href="{{ $q->url }}" title="{{ $q->url }}">Go to this page</a></td>
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
						<th>TSV size</th>
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
								{{ human_date_time($q->start_time, 'H:i:s') }}
							</td>
							<td>

								{{ str_limit($q->url, $limit = 84, $end = '‥') }}
								
								<!-- Button trigger modal -->
								<button type="button" class="btn btn-default btn-xs" data-toggle="modal" data-target="#myModal_{{ $q->id }}">
								  JSON query
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
								      	<pre>{{ json_encode(json_decode($q->params),JSON_PRETTY_PRINT) }}</pre>
								      </div>
								      <div class="modal-footer">
								        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
								      </div>
								    </div>
								  </div>
								</div>

							</td>
							<td>
								@if (isset($q->result_size) && isset($q['params']) && is_string($q['params']) && str_contains($q['params'], 'tsv'))
									{{ human_filesize($q->result_size) }}
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
						</tr>
					@endforeach
				</tbody>
			</table>

		</div>
	</div>
</div>
@stop

