@extends('template')

@section('title', 'Databases')

@section('content')
<div class="container">
	<h1>Databases <small>Choose those available to ALL users of this gateway</small></h1>

	@if (isset($notification))
	<div class="alert alert-warning alert-dismissible" role="alert">
		<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
		{!! $notification !!}
	</div>
	@endif

	<div class="row">
		<div class="col-md-12">

			<table class="table table-bordered table-striped rs_list">
				<thead>
					<tr>
						<th>Enabled</th>
						<th>Name</th>
						<th>URL</th>
						<th></th>
					</tr>
				</thead>
				<tbody>
					@foreach ($rs_list as $rs)
						<tr>
							<td>{{ Form::checkbox('rs_enabled', $rs->id, $rs->enabled) }}</td>					
							<td>{{ $rs->name }}</td>	
							<td><a href="{{ $rs->url }}">{{ $rs->url }}</a></td>					
							<td><a href="{{ $rs->url }}v2/samples?username={{ Auth::user()->username }}">/v2/samples</a></td>
							<td><a href="{{ $rs->url }}v2/sequences_summary?username={{ Auth::user()->username }}">/v2/sequences_summary</a></td>
						</tr>
					@endforeach
				</tbody>
			</table>

			<p>
				<a href="/admin/samples/update-cache">
					<button type="button" class="btn btn-default" aria-label="Edit">
						<span class="glyphicon glyphicon-repeat" aria-hidden="true"></span>
						Refresh cached samples
					</button>
				</a>
			</p>

		</div>
	</div>
</div>
@stop

