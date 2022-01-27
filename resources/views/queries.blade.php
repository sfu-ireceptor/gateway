@extends('template')

@section('title', 'User Queries')

@section('content')
<div class="container-fluid">
	<h1>User activity</h1>

	<ul class="nav nav-tabs">
	  <li role="presentation" class="active"><a href="/admin/queries">Queries</a></li>
	  <li role="presentation"><a href="/admin/downloads">Sequence Downloads</a></li>
	</ul>

	<p></p>

	<div class="row">
		<div class="col-md-10">

			<table class="table table-bordered table-striped table-condensed">
				<thead>
					<tr>
						<th>User</th>
						<th>Start</th>
						<th>URL</th>
						<th>Type</th>
						<th>Result Size</th>
						<th>Duration</th>
					</tr>
				</thead>
				<tbody>
					@foreach ($queries as $q)
						<tr>
							<td>{{ $q->username }}</td>
							<td class="text-nowrap">
								<span class="minor">{{ human_date_time($q->start_time, 'D') }}</span>
								{{ human_date_time($q->start_time, 'M j') }}
								<span class="minor">{{ human_date_time($q->start_time, 'H:i') }}</span>
							</td>
							<td>
								<a href="/admin/queries/{{ $q->id }}" title="{{ $q->url }}">{{ str_limit(url_path($q->url), $limit = 70, $end = '‥') }}</a>

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
						</tr>
					@endforeach
				</tbody>
			</table>

		</div>
		<div class="col-md-2">
			<h2>Notes</h2>

			@if($nb_months == NULL)
				<p>Only the queries done over the last 7 days are shown. <a href="/admin/queries/months/2">See queries from last 2 months</a>.	</p>
			@endif

			<h3>Gateway timeouts</h3>
			<p>
				Loading a page: <strong>{{ $gateway_request_timeout }} sec</strong><br>
				Generating a file: <strong>{{ $gateway_file_request_timeout }} sec</strong>
			</p>

			<h3>Service timeouts</h3>
			<p>
				/repertoire (JSON): <strong>{{ $service_request_timeout_samples }} sec</strong><br>
				/rearrangement (JSON): <strong>{{ $service_request_timeout }} sec</strong><br>
				/rearrangement (TSV): <strong>{{ $service_file_request_timeout }} sec</strong>
			</p>
		</div>
	</div>
</div>
@stop

