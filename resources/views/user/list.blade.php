@extends('template')

@section('title', 'Users')

@section('content')
<div class="container">
	<h1>Users</h1>

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
						<th class="text-nowrap">Last Login</th>
						<th class="text-nowrap">Name</th>
						<th class="text-nowrap">Username</th>
						<th class="text-nowrap">Email</th>
						<th class="text-nowrap">Status</th>
						<th class="text-nowrap">Confirmed</th>
						<th class="text-nowrap">Country</th>
						<th class="text-nowrap">Institution</th>
						<th class="text-nowrap">Action</th>
					</tr>
				</thead>
				<tbody>
					@foreach ($l as $t)
						<tr>
							<td class="text-muted text-nowrap" title="{{ human_date_time($t->created_at, 'M j, Y') }}">
								{{ human_date_time($t->created_at, 'M d, Y') }}
							</td>			
							<td class="text-muted text-nowrap">
								{{ human_date_time($t->last_login, 'M d, Y') }}
							</td>
							<td class="text-muted text-nowrap">
								<a href="/admin/edit-user/{{ $t->id }}">{{ str_limit($t->first_name . " " . $t->last_name , $limit = 15, $end = '‥') }}</a>
							</td>
							<td class="text-nowrap">
								{{ str_limit($t->username,$limit = 15, $end = '‥') }}
								@if ($t->admin)
									<strong>(ADMIN)</strong>
								@endif
							</td>
							<td class="text-nowrap">
								<a href="mailto:{{ $t->email }}">{{ str_limit($t->email,$limit = 15, $end = '‥') }}</a>
								<!-- <a href="/admin/delete-user/{{ $t->username }}">
									<button type="button" class="btn btn-default" aria-label="Delete">
									  <span class="glyphicon glyphicon-trash" aria-hidden="true"></span> Delete
									</button>
								</a>-->
							</td>
							<td class="text-nowrap">
								{{ $t->status }}
							</td>
							<td class="text-nowrap">
								{{ human_date_time($t->email_confirmed_date, 'M d, Y') }}
							</td>
							<td class="text-nowrap">
								{{ str_limit($t->country,$limit = 15, $end = '‥')}}
							</td>
							<td class="text-nowrap">
                                {{str_limit($t->institution, $limit = 15, $end = '‥')}}
							</td> 
							<td class="text-nowrap">
                                @if(str_contains($t->status, '-Approval Pending'))
                                   <a href="/admin/approve-user/{{ $t->id }}" title="Approve">Approve</a>
                                @endif
                                <a href="/admin/reset-password/{{ $t->id }}" title="Reset Password">Reset Password</a>
							</td>
						</tr>
					@endforeach
				</tbody>
			</table>

		</div>
	</div>
</div>
@stop

