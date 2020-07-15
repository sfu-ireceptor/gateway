@extends('template')

@section('title', 'User Sequence Downloads')

@section('content')
<div class="container-fluid">
	<h1>User activity</h1>

	<ul class="nav nav-tabs">
	  <li role="presentation"><a href="/admin/queries">Queries</a></li>
	  <li role="presentation" class="active"><a href="/admin/downloads">Sequence Downloads</a></li>
	</ul>

	<p></p>

	<table class="table table-bordered table-striped table-condensed download_list">
		<thead>
			<th>User</th>
			<th>Date</th>
			<th>Page URL</th>
			<th>Nb sequences</th>
			<th>Duration (running)</th>
			<th>Status</th>
			<th>File</th>
		</thead>
		<tbody>
			@foreach ($download_list as $d)
			<tr>
				<td>{{ $d->username }}</td>
				<td class="text-nowrap">
					{{ $d->createdAt() }}
					</a>
				</td>
				<td>
					<a href="{{ $d->page_url }}">
						{{ str_replace('&', ', ', urldecode($d->page_url)) }}
					</a>
				</td>
				<td>
					{{ $d->nb_sequences }}
				</td>
				<td>
					@if($d->isDone() || $d->isFailed())
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
					@elseif($d->isFailed())
						<span class="label label-danger">{{ $d->status }}</span>
					@elseif($d->isCanceled())
						<span class="label label-default">{{ $d->status }}</span>
					@endif
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
							<a href="{{ $d->file_url }}" class="btn btn-primary download_repertoires btn-xs" type="button" title="Download">
								<span class="glyphicon glyphicon-download-alt" aria-hidden="true"></span>
								<span class="text">
									Download
									({{ human_filesize(filesize($d->file_url)) }})
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

