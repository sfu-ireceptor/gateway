@extends('template')

@section('title', 'Bookmarks')

@section('content')
<div class="container">
	
	<h1>Bookmarks</h1>

	@if (isset($notification))
	<div class="alert alert-warning alert-dismissible" role="alert">
		<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
		{{ $notification }}
	</div>
	@endif
	
	@if (count($bookmark_list_grouped_by_month) == 0)
	<p>
		<em>No bookmarks to show.</em>
	</p>
	@endif
	

	@foreach ($bookmark_list_grouped_by_month as $month => $bookmark_list)
	<h2>{{ $month }}</h2>
		<table class="table table-striped bookmark_list">

			<tbody>
				@foreach ($bookmark_list as $bookmark)
				<tr>
					<td class="text-nowrap">


						{{ $bookmark->createdAt() }}
						</a><br />
						<em class="dateRelative">{{ $bookmark->createdAtRelative() }}</em>
					</td>
					<td>
						<a href="{{ $bookmark->url }}">
							{{ str_replace('&', ', ', urldecode($bookmark->url)) }}
						</a>
					</td>
					<td>
						<a href="/bookmarks/delete/{{ $bookmark->id }}">
							<button type="button" class="btn btn-default" aria-label="Delete">
							  <span class="glyphicon glyphicon-trash" aria-hidden="true"></span> Delete
							</button>
						</a>
					</td>
				</tr>
				@endforeach
			</tbody>
		</table>
	@endforeach

</div>
@stop