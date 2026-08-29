@extends('template')

@section('title', 'My account')

@section('content')
<div class="container">
	
	<h1>My account</h1>

	@if (isset($notification))
	<div class="alert alert-warning alert-dismissible" role="alert">
		<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
		{!! $notification !!}
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
					<p>
                        <strong>Subscription</strong>
                        <br /> {{ $user->status}}
	                    @if (str_contains($user->status, "Commercial"))
                            (<a href="https://billing.stripe.com/p/login/bJecN7bpo0oObx6alBbfO00">Manage your subscription</a>)
                        @endif
	                    @if (str_contains($user->status, "Limited"))
<p>Note: iReceptor uses subscriptions to manage access to the iReceptor Gateway. You have a "Limited" account because you have either not confirmed your Academic email or have not paid for a Commercial subscription.

<p> If you are an academic user, please change the email you are using to your academic email in your personal information.

<p> If you are using an academic email and still have a limited account, this is because your academic email is not from an easily identified academic domain name. Please can request an Academic Approval below. If you choose this option, the iReceptor team will contact you to determine your eligibility for an Academic subscription.
<br><a href="/user/request-academic-upgrade">Request Academic Approval</a>

<p> If you are a Commercial user, please go to the <a href="/register">Subscribe</a> page and choose a commercial subscription package.
                        @endif
                    </p>
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
			  		<p><strong>Country</strong><br /> {{ $user->country}}</p>
			  		<p><strong>Institution</strong><br /> {{ $user->institution}}</p>
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
