 @extends('template')

@section('title', 'User Downloads')

@section('content')
<div class="container-fluid">
	<h1>User activity</h1>

	<ul class="nav nav-tabs">
	  <li role="presentation"><a href="/admin/queries">Queries</a></li>
	  <li role="presentation" class="active"><a href="/admin/downloads">Downloads</a></li>
	  <li role="presentation"><a href="/admin/jobs">Jobs</a></li>
	</ul>

	<p></p>

	<table class="table table-bordered table-striped table-condensed download_list">
		<thead>
			<th>User</th>
			<th>Queued on</th>
			<th>Page URL</th>
			<th>Nb</th>
			<th>Duration (running)</th>
			<th>Status</th>
			<th>Size</th>
			<th>File</th>
		</thead>
		<tbody>
			@foreach ($download_list as $d)
			<tr>
				<td>{{ $d->username }}</td>
				<td class="text-nowrap">
					<span class="minor">{{ human_date_time($d->created_at, 'D') }}</span>
					{{ human_date_time($d->created_at, 'M j') }}
					<span class="minor">{{ human_date_time($d->created_at, 'H:i') }}</span>
				</td>				
				<td>
					@if($d->query_log_id)
						<a href="/admin/queries/{{ $d->query_log_id }}" title="{{ $d->page_url }}">
							{{ str_limit(url_path($d->page_url), $limit = 70, $end = '‥') }}
						</a>
					@else
						{{ str_replace('&', ', ', urldecode($d->page_url)) }}
					@endif
				</td>
				<td>
					{{ number_format($d->nb_sequences) }}
				</td>
				<td>
					@if($d->isDone() || $d->isFailed() || $d->isRunning())
						{{ $d->durationHuman() }}
					@endif
				</td>
				<td>
					@if($d->isQueued())
						<span class="label label-info">{{ $d->status }}</span>
					@elseif($d->isRunning())
						<span class="label label-warning">{{ $d->status }}</span>
					@elseif($d->isDone())
						<span class="label label-success">Finished</span>

						@if($d->isIncomplete())
							<span class="help help_queue_position text-danger" role="button" data-container="body" data-toggle="popover_form_field" data-placement="right" title="Incomplete download" data-content="<p>{{ $d->incomplete_info ? nl2br($d->incomplete_info, true) : 'See the included info.txt file for more details.'}}</p>" data-trigger="hover" tabindex="0">
								<span class="glyphicon glyphicon-warning-sign"></span>
							</span>
						@endif
					@elseif($d->isFailed())
						<span class="label label-danger">{{ $d->status }}</span>
					@elseif($d->isCanceled())
						<span class="label label-default">{{ $d->status }}</span>
					@endif
				</td>
				<td>
					{{ human_filesize($d->file_size) }}
				</td>
				<td>
					@if($d->isQueued())
						<a href="/downloads/cancel/{{ $d->id }}" class="btn btn-warning btn-xs" type="button" title="Cancel Download">
							<span class="glyphicon glyphicon-remove" aria-hidden="true"></span>
							<span class="text">Cancel Download</span>
						</a>
					@elseif($d->isDone() && ($d->file_url != '') && $d->isExpired())
						<em>Expired</em>

					@elseif($d->isDone())
							<a href="/downloads/download/{{ $d->id }}" class="btn btn-primary btn-xs" type="button" title="Download">
								<span class="glyphicon glyphicon-download-alt" aria-hidden="true"></span>
								<span class="text">
									Download
								</span>
							</a>
					@endif
				</td>

			</tr>
			@endforeach
		</tbody>
	</table>	

</div>
@stop

