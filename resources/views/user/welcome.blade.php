@extends('template')

@section('title', 'Welcome')
 
@section('content')
<div class="container">
	
	<h1>Welcome to iReceptor</h1>

	<div class="row">

		<div class="col-md-5">

<p>Your account has been successfully created.
You will receive an email shortly with your username and password
that will enable you to log. Please use the
<a href="/user/account">User Account page</a> to change your password.
</p>
<p>Your account has been assigned either
an <b>Academic</b> or <b>Academic-Approval Pending</b>
status, depending on whether your email is from a recognized academic
institution. If your status is <b>Academic</b> you can log in to your account
and use the iReceptor Gateway immediately. If your status is
<b>Academic-Approval Pending</b> your account is enabled with
limited access to the iReceptor Gateway as we review and approve your
request for an <b>Academic</b> subscription.
You will receive an email notification when your <b>Academic</b> subscription
has been approved.
</p>
<p>
You can view the status of your
iReceptor Subscription at the <a href="/user/account">User Account page</a>.
</p>

<p>
<a href="https://ireceptor.org/platform/doc">
Documentation on how to use the site</a>
</p>
	  
<p>
<a href="mailto:support@ireceptor.org">Let us know</a>
if you have questions, problems, or feedback.
</p>

			<p class="button_botttom_container">
				<a role="button" class="btn btn-primary"  href="/home">
					Proceed to home page →
				</a>
			</p>

		</div>

	</div>

</div>
@stop 
