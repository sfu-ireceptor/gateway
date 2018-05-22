@extends('template')

@section('title', 'News')

@section('content')
<div class="container">
	<h1>News</h1>

	@if (isset($notification))
	<div class="alert alert-warning alert-dismissible" role="alert">
		<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
		{!! $notification !!}
	</div>
	@endif

	<div class="row">
		<div class="col-md-12">

			<table class="table table-bordered table-striped news_list">
				<thead>
					<tr>
						<th>Date</th>
						<th>Message</th>
					</tr>
				</thead>
				<tbody>
					@foreach ($news_list as $n)
						<tr>
							<td>{{ $n->created_at }}</td>	
							<td>{{ $n->message }}</td>	
						</tr>
					@endforeach
				</tbody>
			</table>
			
		</div>
	</div>
</div>
@stop

