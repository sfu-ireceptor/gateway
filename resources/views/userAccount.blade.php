@extends('template')

@section('title', 'My account')

@section('content')
<div class="container">
	
	<h1>My account</h1>

	@if (isset($notification))
	<div class="alert alert-warning alert-dismissible" role="alert">
		<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
		{{ $notification }}
	</div>
	@endif


	<div class="row">

		<div class="col-md-4">
			<div class="panel panel-info">
			  <div class="panel-heading">
			    <h3 class="panel-title">Login info</h3>
			  </div>
			  <div class="panel-body">
					<p><strong>Username</strong><br /> {{ $user->username}}</p>
			  		<p><strong>Password</strong><br /> <a href="/user/change-password">Change password</a></p>
			  </div>
			</div>
		</div>
		<div class="col-md-4">
			<div class="panel panel-info">
			  <div class="panel-heading clearfix">
			    <h3 class="panel-title pull-left">Personal info</h3>
			  </div>
			  <div class="panel-body">
			  		<p><strong>First Name</strong><br /> {{ $user->first_name}}</p>
			  		<p><strong>Last name</strong><br /> {{ $user->last_name}}</p>
			  		<p><strong>Email</strong><br /> {{ $user->email}}</p>
			  		<p>
			  			<a href="/user/change-personal-info" class="pull-right">
				  			<button type="button" class="btn btn-default" aria-label="Edit">
				  				<span class="glyphicon glyphicon-edit" aria-hidden="true"></span>
				  				Change personal info
				  			</button>
			  			</a>
			  		</p>
			  </div>
			</div>
		</div>

	</div>

</div>
@stop