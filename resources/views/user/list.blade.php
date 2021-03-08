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
						<th class="text-nowrap">Name / Username</th>
						<th class="text-nowrap">Email</th>
						<th class="text-nowrap">Last Login</th>
						<th class="text-nowrap">Stats Pop-up Usage</th>
					</tr>
				</thead>
				<tbody>
					@foreach ($l as $t)
						<tr>
							<td class="text-muted text-nowrap" title="{{ Carbon\Carbon::createFromFormat('YmdGisZ', $t->create_time)->format('M j, Y') }}">
								{{ Carbon\Carbon::createFromFormat('YmdGisZ', $t->create_time)->format('M Y') }}
							</td>			
							<td class="text-nowrap">
								<a href="/admin/edit-user/{{ $t->username }}">{{ $t->first_name }} {{ $t->last_name }}</a>
								/
								{{ $t->username }}
								{{ $t->admin ? '(admin)' : '' }}
							</td>
							<td class="text-nowrap">
								<a href="mailto:{{ $t->email }}">{{ $t->email }}</a>
								<!-- <a href="/admin/delete-user/{{ $t->username }}">
									<button type="button" class="btn btn-default" aria-label="Delete">
									  <span class="glyphicon glyphicon-trash" aria-hidden="true"></span> Delete
									</button>
								</a>-->
							</td>
							<td class="text-muted text-nowrap">
								{{ $t->updated_at ? Carbon\Carbon::parse($t->updated_at)->format('M d, Y') : '' }}
							</td>
							<td class="text-nowrap">
								{{ $t->stats_popup_count > 0 ? $t->stats_popup_count : '' }}
							</td>
						</tr>
					@endforeach
				</tbody>
			</table>

		</div>
	</div>
</div>
@stop

