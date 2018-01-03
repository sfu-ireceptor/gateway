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
								{{ human_date_time($q->start_time) }}
							</td>
							<td>
								<a href="{{ $q->url }}" title="{{ $q->url }}">{{ str_limit($q->url, $limit = 64, $end = '‥') }}</a>

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

								<a href="/admin/queries/{{ $q->id }}" class="btn btn-primary btn-xs">Details</a>

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

			@if(! $all)
				<p>Note: only the queries done over the last 7 days are shown. <a href="/admin/queries/all">See all queries</a>.	</p>
			@endif
		</div>
	</div>
</div>
@stop

