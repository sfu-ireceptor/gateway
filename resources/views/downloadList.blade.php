@extends('template')

@section('title', 'Downloads')

@section('content')
<div class="container page-refresh" data-page-refresh-interval="{{ config('ireceptor.sequences_downloads_refresh_interval') }}">
	
	<h1>Sequence Downloads</h1>

	@if (session('download_page'))
	<div class="alert alert-success alert-dismissible" role="alert">
		<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
		<p>
			Your data is being assembled. <strong>You will get an email when it's ready to be downloaded.</strong><br>
			It is safe to leave this page. You can return to it at any time by selecting <code>Downloads</code> under the top-right <code>{{ auth()->user()->username }}</code> menu.
		</p>
		<p>
			<a role="button" class="btn btn-primary browse_sequences browse-seq-data-button button_to_enable_on_load"  href="{!! session('download_page') !!}">
				← Back to Sequence Search
			</a>
		</p>
	</div>
	@endif

	@if (session('deleted_id'))
	<div class="alert alert-warning alert-dismissible" role="alert">
		<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
		Your download has been deleted
		<a class="btn btn-default btn-xs" type="button" href="/downloads/undo-delete/{!! session('deleted_id') !!}">
			Undo
		</a>
	</div>
	@endif

	@if (session('undo_deleted_id'))
	<div class="alert alert-warning alert-dismissible" role="alert">
		<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
		Your download has been restored.
	</div>
	@endif

	<p>Note: this page will automatically refresh every {{ config('ireceptor.sequences_downloads_refresh_interval') }} seconds.<br></p>

	<table class="table table-striped download_list">
		<thead>
			<th>Creation Date</th>
			<th>Status</th>
			<th>Duration</th>
			<th>Sequence Search Page</th>
			<th>Nb sequences</th>
			<th></th>
			<th>Expiration Date</th>
		</thead>
		<tbody>
			@foreach ($download_list as $d)
			<tr class="{{ (session('download_page') && $loop->iteration == 1) ? 'new-item' : ''}}">
				<td class="text-nowrap">
					{{ $d->createdAtShort() }}
					<br />
					<em class="dateRelative">{{ $d->createdAtRelative() }}</em>
				</td>
				<td>
					<h4>
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
					</h4>
				</td>
				<td>
					@if($d->isQueued())
						<em>0 seconds</em>
					@elseif($d->isDone() || $d->isFailed())
						{{ $d->durationHuman() }}
					@endif
				</td>				<td>
					<a href="{{ $d->page_url }}">
						{{ str_replace('&', ', ', urldecode($d->page_url)) }}
					</a>
				</td>
				<td>
					{{ $d->nb_sequences }}
				</td>
				<td>
					@if($d->isQueued())
						<a href="/downloads/cancel/{{ $d->id }}" class="btn btn-warning" type="button" title="Cancel Download">
							<span class="glyphicon glyphicon-remove" aria-hidden="true"></span>
							<span class="text">Cancel Download</span>
						</a>
					@elseif($d->isDone() && ($d->file_url != '') && $d->isExpired())
						Expired
						<span class="help" role="button" data-container="body" data-toggle="popover_form_field" data-placement="right" title="Your download has expired" data-content="<p>Your file is no longer available, but you can go the original page and generate a new download.</p>" data-trigger="hover" tabindex="0">
							<span class="glyphicon glyphicon-question-sign"></span>
						</span>
					@elseif($d->isFailed())
						<em>
							Sorry, an error occured.<br>
							<a href="{{ $d->page_url }}">Try again</a>
							or
							<a href="mailto:{{ config('ireceptor.email_support') }}">let us know</a> so we can help.
						</em>
					@elseif($d->isDone())
							<a href="{{ $d->file_url }}" class="btn btn-primary download_repertoires" type="button" title="Download">
								<span class="glyphicon glyphicon-download-alt" aria-hidden="true"></span>
								<span class="text">
									Download
									({{ human_filesize(filesize($d->file_url)) }})
								</span>
							</a>

						<a href="/downloads/delete/{{ $d->id }}" class="btn btn-warning" type="button" title="Delete Download">
							<span class="glyphicon glyphicon-remove" aria-hidden="true"></span>
							<span class="text">Delete Download</span>
						</a>

					@endif
				</td>
				<td class="text-nowrap">
					@if($d->isDone() && ! $d->isExpired())
						{{ $d->expiresAt() }}
					@endif
				</td>
			</tr>
			@endforeach
		</tbody>
	</table>	


</div>
@stop