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

	<p>
		<a href="/admin/add-news">
			<button type="button" class="btn btn-default" aria-label="Edit">
				<span class="glyphicon glyphicon-plus" aria-hidden="true"></span>
				Add
			</button>
		</a>
	</p>

	<div class="row">
		<div class="col-md-12">

			<table class="table table-bordered table-striped news_list">
				<thead>
					<tr>
						<th class="text-nowrap">Date</th>
						<th class="text-nowrap">Message</th>
						<th class="text-nowrap"></th>
						<th class="text-nowrap"></th>
					</tr>
				</thead>
				<tbody>
					@foreach ($news_list as $n)
						<tr>
							<td class="text-nowrap">{{ Carbon\Carbon::parse($n->created_at)->format('M d, Y') }}</td>	
							<td>
								{{ $n->message }}
							</td>
							<td>
								<a href="/admin/edit-news/{{ $n->id }}">
									<button type="button" class="btn btn-default" aria-label="Edit">
										<span class="glyphicon glyphicon-edit" aria-hidden="true"></span>
										Edit
									</button>
								</a>
							</td>	
							<td>
								<a href="/admin/delete-news/{{ $n->id }}">
									<button type="button" class="btn btn-default" aria-label="Edit">
										<span class="glyphicon glyphicon-remove" aria-hidden="true"></span>
										Delete
									</button>
								</a>
							</td>	
						</tr>
					@endforeach
				</tbody>
			</table>

		</div>
	</div>
</div>
@stop

