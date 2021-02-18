@extends('template')

@section('title', 'Users')

@section('content')
<div class="container">
	<h1>Users <small>via Agave</small></h1>

	@if (isset($notification))
	<div class="alert alert-warning alert-dismissible" role="alert">
		<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
		{!! $notification !!}
	</div>
	@endif

	<p>
		<a href="/admin/add-user">
			<button type="button" class="btn btn-default" aria-label="Edit">
				<span class="glyphicon glyphicon-plus" aria-hidden="true"></span>
				Add a new user
			</button>
		</a>
	</p>



	<div class="row">
		<div class="col-md-4">

			<table class="table table-bordered table-striped rs_list">
				<thead>
						<th class="text-nowrap">Added</th>
						<th class="text-nowrap">First Name</th>
						<th class="text-nowrap">Last Name</th>
						<th class="text-nowrap">Email</th>
					</tr>
				</thead>
				<tbody>
					@foreach ($l as $t)
						<tr>
							<td class="text-muted text-nowrap" title="{{ Carbon\Carbon::createFromFormat('YmdGisZ', $t->create_time)->format('M j, Y') }}">
								{{ Carbon\Carbon::createFromFormat('YmdGisZ', $t->create_time)->format('M d, Y') }}
							</td>			
							<td class="text-nowrap">
								{{ $t->first_name }}
							</td>
							<td>
								{{ $t->last_name }}
							</td>
							<td class="text-nowrap">
								<a href="mailto:{{ $t->email }}">{{ $t->email }}</a>
							</td>
						</tr>
					@endforeach
				</tbody>
			</table>

		</div>
	</div>
</div>
@stop

