@extends('template')

@section('title', 'Downloads')

@section('content')
<div class="container">
	
	<h1>Sequence Downloads</h1>

	@if (isset($notification))
	<div class="alert alert-warning alert-dismissible" role="alert">
		<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
		{!! $notification !!}
	</div>
	@endif


	<table class="table table-striped download_list">
		<thead>
			<th>Date</th>
			<th>Page URL</th>
			<th>Nb sequences</th>
			<th>Status</th>
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
					@if($d->isDone())
						({{ $d->durationHuman() }})
					@elseif($d->isQueued())
						| <a href="/downloads/cancel/{{ $d->id }}">Cancel</a>						
					@elseif($d->isFailed())
						({{ $d->durationHuman() }})
					@endif
				</td>
				<td>
					@if($d->isDone())
							<a href="{{ $d->file_url }}" class="btn btn-primary download_repertoires" type="button" title="Download">
								<span class="glyphicon glyphicon-download-alt" aria-hidden="true"></span>
								<span class="text">Download</span>
							</a>
					@endif
				</td>
			</tr>
			@endforeach
		</tbody>
	</table>	


</div>
@stop