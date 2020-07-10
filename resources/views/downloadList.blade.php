@extends('template')

@section('title', 'Downloads')

@section('content')
<div class="container">
	
	<h1>Downloads</h1>

	@if (isset($notification))
	<div class="alert alert-warning alert-dismissible" role="alert">
		<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
		{!! $notification !!}
	</div>
	@endif


	<table class="table table-striped download_list">
		<thead>
			<th>Date</th>
			<th>Status</th>
			<th>Duration</th>
			<th>Nb sequences</th>
			<th>Page URL</th>
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
					{{ $d->status }}
					@if($d->isDone())
						| <a href="{{ $d->file_url }}">Download</a>
					@elseif($d->isQueued())
						| <a href="/downloads/cancel/{{ $d->id }}">Cancel</a>						
					@endif
				</td>
				<td>
					{{ $d->durationHuman() }}
				</td>
				<td>
					{{ $d->nb_sequences }}
				</td>
				<td>
					<a href="{{ $d->page_url }}">
						{{ str_replace('&', ', ', urldecode($d->page_url)) }}
					</a>
				</td>
			</tr>
			@endforeach
		</tbody>
	</table>	


</div>
@stop