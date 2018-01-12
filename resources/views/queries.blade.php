@extends('template')

@section('title', 'User Queries')

@section('content')
<div class="container">
	<h1>User Queries</h1>

	<div class="row">
		<div class="col-md-9">

			<table class="table table-bordered table-striped table-condensed">
				<thead>
					<tr>
						<th>Start</th>
						<th>URL</th>
						<th>Type</th>
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
								<a href="/admin/queries/{{ $q->id }}" title="{{ $q->url }}">{{ str_limit($q->url, $limit = 50, $end = '‥') }}</a>

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
		<div class="col-md-3">
			<h2>Notes</h2>

			@if(! $all)
				<p>Only the queries done over the last 7 days are shown. <a href="/admin/queries/all">See all queries</a>.	</p>
			@endif

			<h3>Gateway timeouts</h3>
			<p>
				Loading a page: <strong>{{ $gateway_request_timeout }} sec</strong><br>
				Generating a file: <strong>{{ $gateway_file_request_timeout }} sec</strong>
			</p>

			<h3>Service timeouts</h3>
			<p>
				Waiting for some JSON: <strong>{{ $service_request_timeout }} sec</strong><br>
				Waiting for a file: <strong>{{ $service_file_request_timeout }} sec</strong>
			</p>
		</div>
	</div>
</div>
@stop

