@extends('template')

@section('title', 'Downloads')

@section('content')
<div class="container">
	
	<h1>Sequence Downloads</h1>

	@if (session('download_page'))
	<div class="alert alert-success alert-dismissible" role="alert">
		<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
		<p>
			Your download has been successfully queued.<br>
			You'll receive an email when your data is ready for download.
		</p>
		<p>
			<a role="button" class="btn btn-primary browse_sequences browse-seq-data-button button_to_enable_on_load"  href="{!! session('download_page') !!}">
				← Back to Sequence Search
			</a>
		</p>
	</div>
	@endif

	<table class="table table-striped download_list">
		<thead>
			<th>Date</th>
			<th>Page URL</th>
			<th>Nb sequences</th>
			<th>Status</th>
			<th></th>
		</thead>
		<tbody>
			@foreach ($download_list as $d)
			<tr>
				<td class="text-nowrap">
					{{ $d->createdAt() }}
					</a><br />
					<em class="dateRelative">{{ $d->createdAtRelative() }}</em>
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
					{{ $d->status }}
					@if($d->isQueued())
					@elseif($d->isRunning())

					@elseif($d->isDone())
						({{ $d->durationHuman() }})
					@elseif($d->isFailed())
						({{ $d->durationHuman() }})
					@elseif($d->isCanceled())
					@endif
				</td>
				<td>
					@if($d->isQueued())
						<a href="/downloads/cancel/{{ $d->id }}" class="btn btn-warning" type="button" title="Cancel Download">
							<span class="glyphicon glyphicon-remove" aria-hidden="true"></span>
							<span class="text">Cancel Download</span>
						</a>
					@elseif($d->isDone() && ($d->file_url != '') && $d->isExpired())
						<em>Expired</em>
						<span class="help" role="button" data-container="body" data-toggle="popover_form_field" data-placement="right" title="Your download has expired" data-content="<p>Your file is no longer available, but you can go the original page and generate a new download.</p>" data-trigger="hover" tabindex="0">
							<span class="glyphicon glyphicon-question-sign"></span>
						</span>

					@elseif($d->isDone())
							<a href="{{ $d->file_url }}" class="btn btn-primary download_repertoires" type="button" title="Download">
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